# 📘 English Version

## **Unified Logging System — Canonical Logging-Domain Architecture**

**Status:** Approved / Canonical
**Purpose:** Authoritative reference for logging-domain semantics and safety rules; subordinate to
the repository authority order and the root Package Reference for current public Runtime behavior.

---

## 1. System Purpose

Build a strict logging architecture that prevents semantic mixing and enables:

* Security audits
* Incident investigations
* Behavior analysis
* Performance optimization

### Core Outcomes

* Each event logged in exactly **one domain**
* Domain-isolated MySQL storage with domain-owned, column-searchable relations
* No secrets or sensitive data logged
* Design extractable into framework-agnostic standalone libraries

---

## 2. Golden Rule: One-Domain Rule

Every logged event belongs to **one domain only**, based on its **primary intent**.

If an action has multiple intents:

* ❌ Do not duplicate the same event
* ✅ Log **separate events** per intent with minimal metadata

---

## 3. Canonical Domains (Final)

1. Authoritative Audit
2. Audit Trail
3. Security Signals
4. Operational Activity
5. Diagnostics Telemetry
6. Delivery Operations

No additional domains are allowed.

---

## 4. Domain Definitions

### 4.1 Authoritative Audit

* Governance-grade, fail-closed
* **Source of truth:** `maa_event_logging_authoritative_audit_outbox` (transactional)
* `maa_event_logging_authoritative_audit_log` is a materialized read model only

---

### 4.2 Audit Trail

* Data exposure & navigation
* Answers “who saw what, when”

---

### 4.3 Security Signals

* Best-effort security indicators
* No control-flow impact
* Non-transactional

---

### 4.4 Operational Activity

* Mutations only (create/update/delete)
* No reads or exports

---

### 4.5 Diagnostics Telemetry

* Technical observability
* Sanitized, no PII, best-effort

---

### 4.6 Delivery Operations

* Async lifecycle (emails, jobs, webhooks)

---

## 5. Unified Pipeline

```
HTTP/UI
 → Recorder
   → Writer/Logger
     → MySQL
```

`HTTP/UI` is a host-side caller at the integration boundary; it is not shipped by this package.

### Responsibilities

* **Recorder**

    * Build DTO
    * Aggregate context
    * Apply policy
* **Writer**

    * Persist DTO only
    * No policy or DTO construction

Controllers/Services must not log directly.

The current Runtime also exposes separate package-owned Admin Query contracts for all six
domains. They provide domain-specific offset/page reads and do not add controllers, UI,
permissions, reporting, or a generic cross-domain query layer. Primitive cursor reads remain a
separate protected path.

---

## 5.1 Failure Semantics (Canonical)

### Non-Authoritative Domains (Best-Effort, Fail-Open)

For the following domains:
- Audit Trail
- Security Signals
- Operational Activity
- Diagnostics Telemetry
- Delivery Operations

**Recorder Contract (Hard Rule):**
- `Recorder::record()` MUST be fail-open and MUST NOT throw under any condition.
- Therefore, the Recorder MUST catch `Throwable` at the top-level boundary of `record()`.
- After catching `Throwable`, the Recorder MUST swallow (never rethrow). If an optional PSR-3
  logger was supplied, it MAY receive a sanitized operational diagnostic; the current Runtime
  has no mandatory primitive last-resort channel.

**Infrastructure Contract (Hard Rule):**
- Storage drivers / repositories MUST remain honest: they MUST NOT swallow.
- Storage/PDO failures MUST use the applicable domain storage exception.
- Admin Query validation, configuration, and execution failures MUST use the applicable domain
  query exceptions.
- Infrastructure MUST NOT apply domain policy or decide the Recorder's fail-open/fail-closed
  boundary.

**Recursion Guard (Hard Rule):**
- Failure reporting MUST NOT call any logging recorder/writer again.
- Failure reporting MUST NOT call another logging recorder/writer or claim an unimplemented
  primitive fallback channel.

### Authoritative Audit (Fail-Closed)

- Authoritative Audit is integrity critical.
- Outbox write failures are NOT best-effort and MUST be handled as system integrity failures.

---

## 6. Normalized Context

Each domain follows its current storage contract. Where present, normalized context includes:

* event_id (UUID)
* actor_type / actor_id
* correlation_id
* request_id
* route_name
* ip_address
* user_agent
* occurred_at (DATETIME(6), **UTC only**)

Fields are not invented for domains whose schema does not store them; for example,
AuthoritativeAudit has no `request_id` field.

---

## 7. request_id vs correlation_id

* **request_id:** one HTTP request
* **correlation_id:** spans multiple requests in one business workflow

---

## 8. Security Hard Rules

Never log:

* passwords
* OTPs
* access tokens
* secrets or keys

URLs:

* path only
* no query strings

`referrer_path` must be sanitized and masked.

---

## 9. Metadata Policy

* Structured JSON only
* Minimal fields
* Size and oversized-value handling follow each domain's current policy and Runtime contract.
* Fail-open domains MAY sanitize, drop, or replace oversized metadata and continue according to
  their domain contract.
* This document does not invent a size or rejection rule for an AuthoritativeAudit payload that
  the current Runtime does not define.
* The future archiver does not redefine recorder metadata policy.

**Read-Mapping Corruption Tolerance (Explicit Exception):**
- Reader implementations MAY swallow JSON decode errors for `metadata` ONLY during read-mapping.
- In case of corruption, `metadata` MUST become `null` and the event MUST still be returned.
- No other swallowing is permitted in readers.

---

## 10. actor_type Normalization and Validation

`actor_type` normalization and validation are governed by each domain's current policy and
contract. This document does not impose one closed global set of values; each domain follows its
own current contract and documents its domain-specific behavior.

---

## 11. Storage Baseline

* Current Runtime persistence is MySQL only (5.7+).
* Storage is domain-isolated and is not required to use exactly one table per domain.
* AuthoritativeAudit owns `maa_event_logging_authoritative_audit_outbox` as its authoritative
  source and `maa_event_logging_authoritative_audit_log` as its materialized read model.
* MongoDB and all other non-MySQL runtime backends are unsupported.
* Deterministic paging: `(occurred_at, id)` where applicable to the current domain contract.

**PDO Numeric Hydration Rule (MySQL):**
- Numeric columns (e.g., BIGINT) MAY be returned as strings by PDO.
- Query mappers MUST accept numeric strings and cast safely (e.g., `is_numeric` then `(int)`), instead of relying on `is_int` only.

---

## 12. Archiving (Deferred; MySQL → MySQL Mode B Only)

* MySQL → MySQL
* `*_archive` tables
* Same schema and indexes
* Separate SQL file
* Move-then-delete only

These are future constraints only. The current package Runtime does not implement archiving,
retention workers, or hot/archive reads. No non-MySQL archive/backend mode is supported; see
`DEFERRED_SCOPE.md`.

---

## 13. Operational Policies (Deferred Defaults)

The following are future operational constraints for separately enabled consumers, archivers, or
delivery workers. They are not implemented by the current package Runtime; the current deferred
boundary is `DEFERRED_SCOPE.md`.

### Outbox Processing

* Exponential retries
* Max attempts (default 10)
* Dead letter on exhaustion
* Lag alerts

### Delivery Retries

* Max attempts: 5
* Exponential backoff
* Terminal failure state

### Archiving

* Default: records > 90 days
* Batch: 10K
* Verified move before delete

---

## 14. Examples

* `login_failed` → Security Signals
* `create_admin` →

    * Authoritative Audit
    * Operational Activity

---

## 15. Document Status

✅ **Approved logging-domain semantics**
Any future change requires a formal architectural review and must remain aligned with the root
Package Reference and repository authority order.

---
