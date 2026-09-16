# UNIFIED_LOGGING_DESIGN

> **Project:** maatify/php-event-logging
> **Status:** CANONICAL logging-domain design (subordinate to repository authority)
> **Scope:** Defines the unified logging architecture, layering, authority boundaries, storage semantics, and forbidden patterns.
> **Terminology Source of Truth:** `docs/architecture/logging/LOG_DOMAINS_OVERVIEW.md`
> **Storage Guidance:** `docs/architecture/logging/LOG_STORAGE_AND_ARCHIVING.md` defines the
> deferred MySQL → MySQL Mode B archive contract; it does not add runtime backends.
> **Runtime Contract:** `EVENT_LOGGING_PACKAGE_REFERENCE.md` remains canonical for public Runtime behavior.

---

## 0) Purpose

This document defines a single unified approach to logging across the system that:

* prevents domain mixing (conceptual confusion)
* enforces consistent layering (HTTP → Domain policy → Storage)
* provides honest failure semantics (no hidden failures except where explicitly permitted)
* preserves a MySQL-only baseline while keeping the approved archive contract deferred
* enables future extraction of each logging domain as a standalone library from host applications

---

## 1) Canonical Domains (6)

**Domain intent and classification rules are defined only in:**

* `docs/architecture/logging/LOG_DOMAINS_OVERVIEW.md`

This design supports exactly **six** domains:

1. Authoritative Audit
2. Audit Trail
3. Security Signals
4. Operational Activity
5. Diagnostics Telemetry
6. Delivery Operations

**Hard rule:** Every event belongs to exactly **one** domain.

---

## 2) High-Level Architecture

All logging domains follow the same conceptual pipeline:

@@@
HTTP / UI / Controllers
|
v
Domain Recorder (public coordination boundary)
|
v
Domain Policy (domain-specific normalization / validation)
|
v
Domain Logger / Writer (storage adapter interface)
|
v
Storage Driver (MySQL only)
@@@

`HTTP / UI / Controllers` are host-side callers shown at the integration boundary; they are not
components shipped by this package.

### 2.1 Recorder and Policy Roles (Mandatory)

A Recorder is the public recording and coordination boundary. It:

* accepts the documented public recording inputs
* delegates domain-specific normalization and validation to the independent domain Policy
* builds the applicable command or write DTO
* delegates persistence to the domain writer
* coordinates the current domain's reliability boundary

The domain Policy is a separate architectural role responsible for domain-specific normalization
and validation under the current domain contract. It does not perform storage I/O or decide the
Recorder's fail-open/fail-closed boundary.

Recorders prevent:

* controllers/services inventing ad-hoc log shapes
* cross-domain contamination
* repeated copy/paste metadata extraction

---

## 3) Layering Rules (Hard Rules)

### 3.1 Allowed Responsibilities by Layer

**Controllers / HTTP**

* MAY call domain recorders (directly or via services that call recorders).
* MUST NOT build logging DTOs manually.
* MUST NOT write directly to storage.

**Domain Services**

* MAY call recorders (preferred) or trigger recorder calls through workflow orchestration.
* MUST NOT write directly to storage drivers.

**Recorders**

* MUST coordinate public recording, applicable Policy delegation, command/write DTO construction,
  and writer delegation.
* MUST NOT contain SQL / direct storage logic.
* MUST delegate current domain policy for data safety and metadata handling (see Section 9 and
  Section 14).

**Infrastructure Drivers**

* MUST be MySQL-specific in the current Runtime.
* MUST NOT reinterpret policy or classify domains.
* MUST surface failures through the current domain exception boundary and never swallow:
  * Storage/PDO failures use the applicable domain storage exception.
  * Admin Query validation, configuration, and execution failures use the applicable domain query
    exceptions.

### 3.2 Forbidden Shortcuts

* Direct `PDO->prepare/execute` from controllers for logging
* Direct external DB writes from controllers for logging
* “One Logger to log everything” design
* Reusing Telemetry to log Data Access (Audit Trail)
* Reusing Operational Activity for views/reads/exports
* Writing Security Signals into Audit tables (or vice versa)

---

## 4) Authority Model

### 4.1 Authoritative vs Non-Authoritative

There are two authority classes:

#### A) Authoritative (Compliance Grade)

* Applies only to **Authoritative Audit**
* Has integrity expectations and controlled writer pipeline
* Uses outbox + materialized log pattern

#### B) Non-Authoritative (Best-Effort)

Applies to:

* Audit Trail
* Security Signals
* Operational Activity
* Diagnostics Telemetry
* Delivery Operations

These logs are operationally important, but not “compliance source of truth”.

---

## 5) Storage Semantics (Canonical Baseline)

### 5.1 Baseline Storage Topology (MySQL Only)

Current persistence is domain-isolated within MySQL. A domain is not required to map to exactly
one table; its topology may contain multiple domain-owned tables. The current canonical relations
include:

* Authoritative Audit:

  * `maa_event_logging_authoritative_audit_outbox` *(authoritative source)*
  * `maa_event_logging_authoritative_audit_log` *(materialized query table; written only by consumer)*

* Audit Trail:

  * `maa_event_logging_audit_trail`

* Security Signals:

  * `maa_event_logging_security_signals`

* Operational Activity:

  * `maa_event_logging_behavior_trace`

* Diagnostics Telemetry:

  * `maa_event_logging_diagnostics_telemetry`

* Delivery Operations:

  * `maa_event_logging_delivery_operations`

### 5.2 Supported Backend Boundary

The current Runtime persistence backend is MySQL only. MongoDB and all other non-MySQL backends
are unsupported; no optional additional runtime backend contract exists. The only approved future
archive contract is deferred MySQL → MySQL Mode B, governed by `DEFERRED_SCOPE.md` and
`LOG_STORAGE_AND_ARCHIVING.md`.

### 5.3 Current Read Paths

The current Runtime has two separate package-owned read paths:

* Protected primitive cursor-based reads for system consumers.
* Domain-specific Admin Query offset/page reads for all six domains.

Admin Query implementations own domain filters, trusted SQL, mapping, and query exception
boundaries, while `maatify/persistence` owns generic pagination mechanics. Neither path provides
a generic cross-domain reader, arbitrary SQL, controllers, permissions, UI, or reporting.
Reporting and dashboard summaries remain future Phase 5 work under `DEFERRED_SCOPE.md`.

---

## 6) Failure Semantics (“Honest Contracts”)

### 6.1 Core Principle

Logging must not fail silently in infrastructure unless explicitly permitted by policy.

### 6.2 Allowed Swallowing (Very Limited)

**Recorder Exception Boundary (Hard Rule):**
- For all **Non-Authoritative** domains, `Recorder::record()` MUST be **fail-open** and MUST NOT throw under any condition.
- Therefore, the Recorder MUST catch **`Throwable` at the top-level boundary** of `record()`.
- Infrastructure MUST remain honest (never swallow) and MUST surface failures through the current
  domain exception boundary: Storage/PDO failures use the applicable domain storage exception;
  Admin Query validation, configuration, and execution failures use the applicable domain query
  exceptions.
- The Recorder MUST swallow after catching `Throwable` (record() MUST NOT throw). If an optional
  PSR-3 logger was supplied, it MAY receive a sanitized diagnostic; no mandatory primitive
  last-resort channel is part of the current Runtime.

**Recursion Guard (Hard Rule):**
- Failure reporting MUST NOT call any logging domain recorder/writer again.
- Failure reporting MUST NOT call another logging recorder/writer or claim an unimplemented
  primitive fallback channel.

* **Non-authoritative recorders MAY treat storage failures as best-effort** if and only if:
  * the swallow is explicit and documented as “best-effort logging”
  * the infrastructure driver itself remains honest (does not swallow)
  * an optional supplied PSR-3 logger MAY receive a sanitized operational diagnostic, without
    creating recursive failures

**Important:** Failure reporting must not cascade into further logging writes. There is no required
primitive fallback channel when an optional PSR-3 logger was not supplied.

### 6.3 Forbidden Swallowing

* Infrastructure drivers MUST NOT swallow exceptions silently.
* “Try/catch empty” in storage drivers is forbidden.
* Best-effort means that a non-authoritative recorder swallows its failure; it does not require a
  diagnostic channel. An optional supplied PSR-3 logger MAY receive a diagnostic, and no mandatory
  reporting or fallback channel exists when no logger was supplied.
* Swallowing is ONLY permitted at the **Recorder boundary** for **Non-Authoritative** domains (best-effort).
* Any swallowing inside Infrastructure/Repository/DTO layers is forbidden.

### 6.3.1 Trait Usage Policy (Strict)
The following traits MUST NOT be used inside Domain, Application Services, Security, or Audit code:
* `Maatify\PsrLogger\Traits\LoggerContextTrait`
* `Maatify\PsrLogger\Traits\StaticLoggerTrait`

**Reason:** They bypass Dependency Injection and violate transactional boundaries.
`StaticLoggerTrait` is permitted **only** in bootstrap scripts, CLI tools, and cron jobs.

**Read-Mapping Corruption Tolerance (Explicit Exception):**
- Reader implementations MAY swallow JSON decode errors for `metadata` ONLY during read-mapping.
- In case of corruption, `metadata` MUST become `null` (best-effort hydration), and the event MUST still be returned.
- No other swallowing is permitted in read-mapping.

### 6.4 Authoritative Audit Semantics

* Authoritative audit MUST preserve integrity.
* If outbox write fails, this is not “best-effort” — it is a system integrity failure and MUST be handled as such.

---

## 7) Canonical Fields & Context (All Domains)

Each log domain event MUST support the following normalized context (where applicable):

* `actor_type` (string)
* `actor_id` (nullable integer)
* `correlation_id` (nullable string)
* `request_id` (nullable string)
* `route_name` (nullable string)
* `ip_address` (nullable string)
* `user_agent` (nullable string)
* `occurred_at` (DATETIME(6))

### 7.1 Timezone Rule (Hard)

* `occurred_at` MUST be stored in **UTC** across ALL domains.
* Application layer MUST convert local time to UTC before insert.
* Display/UI layer is responsible for timezone rendering per user.

### 7.1.1 Numeric Hydration Rule (PDO / MySQL)
- For MySQL drivers, numeric columns (e.g., BIGINT) MAY be returned as strings by PDO.
- Query mappers MUST treat numeric strings as valid and cast safely (e.g., `is_numeric` + `(int)`), instead of relying on `is_int`.

### 7.2 Event Identity (Recommended)

For non-authoritative domains:

* `event_id` (UUID string) is strongly recommended for idempotency and traceability.

### 7.3 Correlation vs Request (Canonical Glossary)

* `request_id`:

  * Unique ID for a single HTTP request/response cycle.
  * Used to correlate logs generated inside the same request.

* `correlation_id`:

  * Links events across multiple requests that belong to one business/operational transaction.
  * Example: multi-step flow spanning multiple requests or async continuation.

---

## 8) Domain-Specific Contracts (What Each Domain Must Provide)

### 8.1 Authoritative Audit

* MUST use controlled write pipeline (outbox + consumer)
* MUST record governance/security posture changes only
* MUST NOT be used for:

  * views/reads/exports
  * security failures
  * diagnostics telemetry
  * delivery lifecycle

### 8.2 Audit Trail

* MUST represent data exposure:

  * reads/views/navigation/exports/downloads
* MUST sanitize URLs and referrers (see Section 9.1 and Section 14.3)
* SHOULD support “subject tracking” when accessing user/customer data

### 8.3 Security Signals

* MUST represent auth/authorization/session anomalies
* MUST include severity
* SHOULD include safe “reason” and minimal metadata
* MUST NOT affect control flow (best-effort)

### 8.4 Operational Activity

* MUST represent mutations and operational actions only
* MUST NOT include views/reads/exports

### 8.5 Diagnostics Telemetry

* MUST represent technical observability:

  * durations, subsystem markers, sanitized errors
* MUST avoid PII and secrets

### 8.6 Delivery Operations

* MUST represent lifecycle of async operations:

  * queued/sent/delivered/failed/retrying
* MUST include attempt counters and safe provider info
* MUST follow retry policy rules (Section 14.6)

---

## 9) Data Safety (Hard Rules)

Logging must NEVER store:

* passwords
* raw OTP codes
* access tokens
* session secrets
* encryption keys
* signed URLs containing secrets

### 9.1 URL Sanitization (Hard)

* store safe paths only (strip query parameters)
* never store full URLs that include tokens/codes/signatures
* path segments MAY contain sensitive tokens in some systems; therefore:

  * any path segments that can contain secrets MUST be masked or hashed
  * schema and documentation MUST explicitly call this out for fields like `referrer_path`

### 9.2 Metadata Discipline (Hard)

* prefer allowlisted keys
* avoid dumping raw payloads
* keep JSON minimal and structured
* apply the current domain policy for size and oversized-metadata handling (Section 14.1)

### 9.3 Current Runtime Gap / RC Blocker

The safety rules above remain canonical architectural requirements. They do not prove that the
current Runtime already enforces every requirement.

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

## 10) Naming & Taxonomy Rules

To prevent confusion, each domain must use a consistent naming model:

* Audit Trail: `event_key` (e.g., `customer.view`, `orders.export`)
* Security Signals: `signal_type` + severity (e.g., `login_failed`, `permission_denied`)
* Operational Activity: `action` (e.g., `customer.update`, `settings.change`)
* Diagnostics Telemetry: `event_key` + metrics (e.g., `http.request`, `db.slow_query`)
* Delivery Operations: `operation_type` + `channel` + `status`

Avoid free-text strings where structured enums/taxonomy exist.

---

## 11) ASCII Documentation Rules

ASCII diagrams must follow:

* [`ASCII Flow Legends`](../../reference/logging/ASCII_FLOW_LEGENDS.md)

No alternative symbols, no informal arrows, no custom markers.
All flow diagrams must use the canonical legend.

---

## 12) Future Library Extraction (Design Constraint)

The code and structure MUST remain compatible with extracting each domain as its own library.

This implies:

* domain-specific contracts are isolated
* shared primitives are minimal
* no “mega logger” module
* no domain-specific policy hidden inside generic tooling

---

## 13) Compliance Checklist (Quick)

A logging implementation is compliant only if:

* It is classified into exactly one of the six domains.
* It routes through a domain recorder and the applicable domain Policy.
* Infrastructure does not silently swallow exceptions.
* Telemetry is not used for access tracking.
* Operational Activity does not contain reads/views.
* Audit Trail contains reads/views/exports/navigation.
* Authoritative Audit uses outbox + consumer pipeline.
* Current persistence remains MySQL only; non-MySQL runtime backends are unsupported.

---

## 14) Operational Policies and Deferred Boundaries

This section records current safety rules and future operational constraints. The package's
current Runtime does not implement outbox consumers, archivers, dashboards, or reporting; the
deferred portions below preserve the requirements for separately approved future work.

### 14.1 Metadata Handling (Domain Policy)

`metadata` size and handling follow each domain's current policy and Runtime contract. This
unified design does not impose a global maximum or a global rejection rule.

For fail-open domains, oversized metadata MAY be sanitized, dropped, or replaced and recording
may continue according to that domain's contract. This document does not invent size or rejection
behavior for an AuthoritativeAudit payload that the current Runtime does not define.

The future archiver copies stored records and does not redefine recorder metadata policy.

**Forbidden patterns:**

* storing full request bodies
* storing full stack traces as metadata payloads
* storing multi-megabyte debug dumps

### 14.2 actor_type Normalization and Validation

`actor_type` normalization and validation are governed by each domain's current policy and
contract. This unified design does not impose one closed global set of values; each domain follows
its own current contract and documents any domain-specific behavior.

### 14.3 Audit Trail referrer_path / URL Safety (Hard)

Even “path-only” can contain sensitive segments.

For any stored path or referrer field:

* Strip all query strings
* Mask or hash sensitive path parameters where tokens/codes/signatures may appear
* Prefer templated representation:

  * Example: `/reset-password/{hashed}` rather than `/reset-password/abc123`

### 14.4 Authoritative Outbox Processing Guarantees (Deferred Consumer Contract)

The outbox pipeline MUST be resilient to consumer failure.

Canonical requirements:

* Consumer MUST be idempotent (keyed by `event_id` or equivalent)
* Consumer MUST retry with exponential backoff
* Consumer MUST stop infinite retries and surface failures

Minimum operational policy (baseline):

* Retry: exponential backoff
* Max attempts: **10**
* After max attempts: move to a **manual intervention queue** (dead-letter semantics) or mark terminal failure in a dedicated status/field

Monitoring requirement:

* Alert if outbox lag exceeds a policy threshold (example: > 5 minutes)

### 14.5 Archiving Trigger Policy (Deferred; MySQL → MySQL Mode B Only)

Archiving is deferred and not required for baseline correctness. If the separately approved Mode B
archiver is implemented, its trigger policy MUST be documented and implemented within the MySQL →
MySQL boundary.

Recommended canonical defaults (adjust per deployment):

* Trigger: records older than **90 days** (domain-specific retention may differ)
* Frequency: daily (off-peak)
* Batch size: **10,000** rows/run (tunable)
* Verification: ensure transfer success before delete (hard rule)
* Rollback safety: if archive fails, hot data stays

### 14.6 Delivery Operations Retry Policy (Deferred Operational Contract)

To avoid infinite loops, Delivery Operations MUST have a bounded retry policy.

Minimum operational policy:

* Max attempts: **5**
* Backoff: exponential (example schedule: 1m, 5m, 15m, 1h, 6h)
* After max: status MUST transition to a terminal failure state (example: `failed_permanent`)
* Terminal failures MUST be discoverable by query/UI

### 14.7 Performance & Scale Guidance (Non-Blocking but Canonical-Aware)

This document does not mandate a specific throughput target, but it mandates design awareness:

* High-volume domains (Telemetry, Security Signals) MUST remain best-effort.
* Index strategy MUST remain aligned with investigation query patterns (actor/time, event_key/time).
* If sustained write contention occurs, the system MAY evolve via:

  * partitioning (future ADR)
  * read replicas for reporting
  * deferred MySQL → MySQL Mode B archiving automation

### 14.8 GDPR / Retention / Right-to-Be-Forgotten (Policy Boundary)

Logs can be compliance-sensitive. Deleting logs may break audit integrity.

Canonical posture:

* Authoritative Audit:

  * MUST NOT be deleted by default (legal/compliance obligation)
* Other domains:

  * MUST follow retention policy
  * For “right-to-be-forgotten” requests:

    * prefer anonymization/pseudonymization (policy decision per domain)
    * never remove integrity-critical governance history

Any GDPR strategy MUST be explicitly documented per deployment, but this design sets the default principle:

* preserve integrity
* minimize personal data
* apply retention
* anonymize where legally required and technically safe

---
