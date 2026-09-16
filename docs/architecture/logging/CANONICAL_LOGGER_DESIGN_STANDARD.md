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

1. **Recorder (Public Recording / Coordinator Boundary)**
2. **Policy (Domain-Specific Normalization / Validation Role)**
3. **Contract (Writer / Logger Interface)**
4. **DTO Layer (Strong Types Only)**
5. **Infrastructure Driver (Storage Adapter)**

No layer may be skipped or merged.

---

### 2.1 Recorder Layer (Public Recording / Coordinator Boundary)

The Recorder is the public recording and coordination boundary. It delegates domain-specific
normalization and validation to the independent domain Policy, builds the applicable command or
write DTO, and delegates persistence to the domain writer.

#### Mandatory Responsibilities

* Accept the documented public recording inputs, including primitive/enum convenience methods and
  domain commands where the current contract provides them
* Delegate domain-specific normalization and validation to the Policy
* Build the applicable command or write DTO required by the current domain contract
* Delegate persistence to the domain writer
* Coordinate the current domain's reliability boundary; swallowing is allowed only for
  non-authoritative best-effort recorder contracts

The Recorder MUST NOT be treated as the owner of every policy rule. Context and metadata handling
remain Policy responsibilities where the current domain contract assigns them there.

### 2.2 Policy Role (Domain-Specific)

Policy is an independent architectural role, not a new required directory or file. Each domain's
Policy is responsible only for the normalization and validation operations explicitly defined by
that domain's current Policy contract. It is not a universal context assembler or a universal
safety-enforcement layer.

The current Runtime Policy contracts expose different combinations of domain behavior, including:

* `actor_type` normalization where the domain Policy contract defines it
* `severity` normalization in the domains whose Policy contracts define it
* metadata JSON-size validation where the current domain contract defines it
* AuthoritativeAudit payload validation

No common Policy contract requires every one of these operations for every domain.

The Policy MUST:

* expose and apply only the domain-specific normalization and validation behavior required by the
  current domain contract
* preserve that domain's documented fallback, normalization, and validation semantics
* leave timestamp, event-id, and complete context assembly to the Recorder where the current
  domain contract assigns those responsibilities
* not be treated as proof that the canonical safety requirements are enforced universally; those
  requirements and the open Runtime gap are recorded in Section 6.4

The Policy MUST NOT:

* SQL or storage logic
* Infrastructure concerns
* decide the Recorder's fail-open or fail-closed boundary

---

### 2.3 Contract Layer (Interfaces)

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

Ad-hoc associative arrays MUST NOT substitute for defined commands, DTOs, or contracts. Domain-
defined `metadata` arrays are permitted where the current domain contract allows them and MUST
follow that domain's Policy.

---

### 2.4 DTO Layer (Strict Discipline)

DTO discipline applies at the boundary where the current domain contract defines a DTO. The
following types are distinct and must not inherit one another's semantics:

* **Commands:** Public mutation input contracts for recorder command methods.
* **Write/persistence DTOs:** Recorder-to-writer values required by the current write and storage
  contracts.
* **Query/request DTOs:** Read filters and pagination inputs governed by the current query
  contract.
* **Page/result/view DTOs:** Read-side output and pagination result values governed by the current
  read contract.

Documented public recorder convenience methods may continue to accept primitive or enum inputs.

#### Naming Rules

* DTO class names MUST end with `DTO`
* Enum names MUST end with `Enum`

#### DTO Properties

* Immutable (readonly where possible)
* Property shapes MUST follow their own current boundary contract.
* Write, query, view, and page/result DTOs MAY contain contract-defined value objects or nested
  DTOs, including `DateTimeImmutable` and `list<...ViewDTO>` where the current Runtime contract
  defines them.
* When a DTO participates in a `JsonSerializable` boundary, its final serialized representation
  MUST be JSON-safe primitive/array values, including recursively serialized nested DTOs, according
  to the current contract.
* Contain only the fields required by their own current boundary contract, such as:

    * domain-specific fields
    * applicable context fields
* MUST NOT contain:

    * secrets
    * raw request payloads
    * values or objects outside their own current boundary contract

---

### 2.5 Infrastructure Drivers (Storage Adapters)

Infrastructure drivers implement the domain contract and perform **I/O only**.

#### Hard Rules

* MUST NOT contain policy logic
* MUST NOT swallow exceptions
* Infrastructure drivers MUST be logging-silent.  
  All observability belongs to the Recorder or Host Application.
* MUST surface failures through the current domain exception boundary
* MUST NOT decide domain policy or the Recorder's fail-open/fail-closed behavior

#### Exception Boundaries

* Storage/PDO failures MUST be translated to the applicable domain storage exception.
* Admin Query validation, configuration, and execution failures MUST use the applicable domain
  query exceptions defined by the current contract.
* These query exception boundaries are not required to be storage exceptions merely because the
  implementation is located under Infrastructure.

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

### 6.4 Current Runtime Gap / RC Blocker

The safety rules in this section remain canonical architectural requirements. They do not prove
that the current Runtime already enforces every requirement.

The current Runtime has an open **Current Runtime gap / RC blocker**:

* `AuditTrailRecorder` strips query strings from `referrerPath` but does not currently mask or
  hash sensitive path segments.
* `Common\UrlSanitizer` does not currently satisfy the canonical path-only plus sensitive
  path-segment masking requirement.
* Non-authoritative metadata sanitization is not universally enforced at Recorder boundaries. The
  existence of `Common\MetadataSanitizer` alone does not prove enforcement.

This is not deferred optional functionality and is not a Host-only responsibility. WU-2 records
the gap only; a separate Runtime remediation WU must close it before RC.

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
* Data safety rules remain canonical requirements; current enforcement status is tracked in
  Section 6.4 as an open RC blocker
* Storage targets are correct and exclusive

---

**END OF CANONICAL LOGGER DESIGN STANDARD**
