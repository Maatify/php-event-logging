# CANONICAL_LOGGER_DESIGN_STANDARD

> **Project:** maatify/php-event-logging
> **Status:** CANONICAL (Binding logging standard — subordinate to repository authority)
> **Scope:** Defines the **mandatory design standard** for building any logging domain as a framework-agnostic standalone, extractable library (uses explicit Composer/runtime dependencies).
> **Terminology Source of Truth:** `docs/architecture/logging/LOG_DOMAINS_OVERVIEW.md`
> **ASCII Language Source of Truth:** [`ASCII_FLOW_LEGENDS.md`](../../reference/logging/ASCII_FLOW_LEGENDS.md)
> **Logging Semantics References:**
>
> * `unified-logging-system.ar.md`
> * `unified-logging-system.en.md`
>
> If a conflict exists, the repository authority order applies; the root Package Reference
> governs current public Runtime behavior.

The single canonical root Package Reference is `EVENT_LOGGING_PACKAGE_REFERENCE.md`. Current
domain types use the package namespace `Maatify\EventLogging\<Domain>`.

---

## 0) Purpose

This standard exists to guarantee that **every logging domain**:

* is architecturally isolated
* has honest and predictable failure semantics
* enforces strict DTO discipline
* respects explicit policy boundaries
* can be extracted later as a framework-agnostic standalone library (uses explicit Composer/runtime dependencies) **without redesign**

This standard applies to **all six logging domains** defined in
`LOG_DOMAINS_OVERVIEW.md`.

---

## 1) Domain Isolation (Hard Rule)

A logging module MUST represent **exactly one logging domain**.

* A domain module MUST NOT accept events from another domain.
* A domain module MUST NOT write to another domain’s storage.
* A domain module MUST NOT reuse another domain logger “for convenience”.

**Important:**
If a real-world action maps to multiple domains, it is **multiple events**,
not a shared event.

---

## 2) Mandatory Architectural Layers

Every logging domain implementation MUST contain the following layers:

1. **Recorder (Policy Boundary)**
2. **Contract (Writer / Logger Interface)**
3. **DTO Layer (Strong Types Only)**
4. **Infrastructure Driver (Storage Adapter)**

No layer may be skipped or merged.

---

### 2.1 Recorder Layer (Policy Boundary)

The Recorder is the **only policy-aware component**.

#### Mandatory Responsibilities

* Construct domain DTOs
* Normalize context:

    * `actor_type`, `actor_id`
    * `request_id`, `correlation_id`
    * `route_name`
    * `ip_address`, `user_agent`
    * `occurred_at` (**UTC only**)
* Enforce metadata policy:

    * allowlisted keys
    * sanitized values
    * size and oversized-value handling according to the current domain policy and Runtime
      contract
* Decide whether storage failures may be swallowed (best-effort domains only)

#### Forbidden Responsibilities

* SQL or storage logic
* Infrastructure concerns
* Business decisions unrelated to logging policy

---

### 2.2 Contract Layer (Interfaces)

Each domain MUST define a stable, explicit contract.

Allowed patterns:

* `DomainLoggerInterface`
* `DomainWriterInterface`

Writer and storage contracts MUST use their applicable domain DTO boundary and return/throw
according to that domain's current contract. Query contracts likewise use their current request,
cursor, page, and result types. Public recorder convenience methods may retain documented
primitive/enum inputs and construct the applicable DTO or command internally.

Examples (conceptual, not code):

* `write(DomainWriteDTO $dto): void`
* `find(DomainQueryDTO $query): array`

❌ Raw arrays are FORBIDDEN.

---

### 2.3 DTO Layer (Strict Discipline)

DTO discipline applies to writer, storage, and query contracts where the current domain contract
defines a DTO boundary. It does not prohibit documented public recorder convenience methods with
primitive or enum inputs.

#### Naming Rules

* DTO class names MUST end with `DTO`
* Enum names MUST end with `Enum`

#### DTO Properties

* Immutable (readonly where possible)
* Serializable into primitives only
* Contain:

    * domain-specific fields
    * normalized context fields
* MUST NOT contain:

    * secrets
    * raw request payloads
    * unserialized objects

---

### 2.4 Infrastructure Drivers (Storage Adapters)

Infrastructure drivers implement the domain contract and perform **I/O only**.

#### Hard Rules

* MUST NOT contain policy logic
* MUST NOT swallow exceptions
* Infrastructure drivers MUST be logging-silent.  
  All observability belongs to the Recorder or Host Application.
* MUST throw **domain-specific storage exceptions**

Supported baseline driver:

* MySQL (PDO)

Archive drivers (if enabled) MUST comply with:

* `LOG_STORAGE_AND_ARCHIVING.md`

---

## 3) Failure Semantics (Honest Contracts)

### 3.1 Infrastructure MUST Throw

Infrastructure drivers:

* ALWAYS throw on failure
* NEVER return boolean success flags
* NEVER silently ignore errors

---

### 3.2 Swallow Is a Policy Decision

Only the Recorder (or explicit project policy boundary) may:

* catch storage exceptions
* optionally swallow them

Swallowing is allowed ONLY when:

* the domain is defined as best-effort
* business flow must not be broken

If a non-authoritative failure is swallowed, an optional PSR-3 logger MAY receive a sanitized
diagnostic when one was supplied. The current Runtime does not require a primitive last-resort
channel or a recursive Diagnostics Telemetry write.

Swallowing MUST be explicit and local.
Generic try/catch at higher layers (services/controllers) is FORBIDDEN.

---

### 3.3 Authoritative Audit — Special Case

Authoritative Audit is **NOT best-effort**.

Rules:

* failures MUST propagate
* swallowing is FORBIDDEN by default
* must use controlled pipeline:

    * outbox (transactional)
    * consumer (materialized log)

Integrity failures MUST block the governed change.

---

## 4) Canonical Context Model (All Domains)

Every domain event MUST follow its current domain contract. Where a field is present in that
contract, it is normalized as follows; a domain must not invent fields absent from its schema
(for example, AuthoritativeAudit does not store `request_id`):

* `actor_type` (validated, enum-like)
* `actor_id`
* `request_id` (single request scope)
* `correlation_id` (multi-request workflow scope)
* `route_name`
* `ip_address`
* `user_agent`
* `occurred_at` (UTC)

### actor_type Normalization and Validation

`actor_type` normalization and validation are governed by each domain's current policy and
contract. This standard does not impose one closed global set of values; each domain must follow
the values and behavior defined by its own current contract.

---

## 5) Storage Target Discipline (Hard Rule)

A domain module MUST write **only** to its canonical storage target.

Storage targets MUST align with:

* `LOG_DOMAINS_OVERVIEW.md`
* `LOG_STORAGE_AND_ARCHIVING.md`

Examples:

* Audit Trail → `maa_event_logging_audit_trail` (+ `_archive` if enabled)
* Security Signals → `maa_event_logging_security_signals` (+ `_archive`)
* Operational Activity → `maa_event_logging_behavior_trace` (+ `_archive`)
* Diagnostics Telemetry → `maa_event_logging_diagnostics_telemetry` (+ `_archive`)
* Delivery Operations → `maa_event_logging_delivery_operations` (+ `_archive`)
* Authoritative Audit → `maa_event_logging_authoritative_audit_outbox` + `maa_event_logging_authoritative_audit_log`

Cross-domain writes are FORBIDDEN.

---

## 6) Data Safety & Sanitization (Hard Rules)

### 6.1 Never Log Secrets

Forbidden in ALL domains:

* passwords
* raw OTP codes
* access tokens
* session secrets
* encryption keys
* signed URLs containing secrets

---

### 6.2 URL Sanitization

If URLs or referrers are logged:

* store path only
* strip query strings
* mask sensitive path segments (tokens, secrets)

---

### 6.3 Metadata Discipline

Metadata MUST be:

* structured
* minimal
* allowlisted where possible
* size and oversized-value handling follow the current domain policy and Runtime contract
* free of PII/secrets

Raw payload dumps are FORBIDDEN.

---

## 7) Taxonomy & Naming Standards

Domains MUST use stable taxonomy keys:

* Audit Trail → `event_key`
* Security Signals → `signal_type`
* Operational Activity → `action`
* Diagnostics Telemetry → `event_key`
* Delivery Operations → `operation_type`, `channel`, `status`

Free-text strings MUST NOT be used as primary classifiers.

---

## 8) Mandatory Diagram Compliance

All diagrams describing logging behavior MUST comply with:

* [`ASCII_FLOW_LEGENDS.md`](../../reference/logging/ASCII_FLOW_LEGENDS.md)

Custom arrows, implicit semantics, or informal notation are INVALID.

---

## 9) Canonical Compliance Checklist

A logging domain implementation is compliant ONLY if:

* Domain is explicit and isolated
* Applicable writer, storage, and query boundaries use their current DTO contracts; documented
  recorder convenience methods remain supported
* Infrastructure throws honest exceptions
* Recorder is the only swallow boundary (if any)
* Context normalization is complete and UTC-based
* Data safety rules are enforced
* Storage targets are correct and exclusive

---

**END OF CANONICAL LOGGER DESIGN STANDARD**
