# LOG_STORAGE_AND_ARCHIVING

> **Project:** maatify/php-event-logging
> **Status:** CANONICAL (Binding storage guidance — subordinate to repository authority)
> **Scope:** Defines the MySQL-only baseline and the deferred MySQL → MySQL Mode B archive
> contract for the Unified Logging System.
> **Terminology Source of Truth:** `docs/architecture/logging/LOG_DOMAINS_OVERVIEW.md`
> **Logging Semantics References:**
>
> * `unified-logging-system.ar.md`
> * `unified-logging-system.en.md`
>
> If a conflict exists, the repository authority order applies; deferred archive behavior is
> governed by `../DEFERRED_SCOPE.md`.

---

## 0) Baseline First (Hard Rule)

The current Runtime persistence MUST be **fully correct and complete using MySQL only**.

* MongoDB and any other non-MySQL backend are **unsupported**; no optional additional runtime
  backend contract exists.
* Future archiving is **deferred** and **NOT required** for baseline correctness.
* Any separately approved future archive implementation MUST:

    * Be explicitly enabled
    * Be documented
    * Preserve searchability by columns
* Any archiving model other than Mode B (MySQL → MySQL) is non-canonical
    and explicitly unsupported.

This rule exists to ensure portability across constrained or shared hosting environments.

---

## 1) Baseline Storage Model (MySQL Only)

### 1.1 Canonical Hot Relations

Persistence is domain-isolated within MySQL. A domain is not required to map to exactly one table;
its current topology may contain multiple domain-owned tables. The following are the current
canonical hot relations:

| Domain                    | MySQL Table                  | Notes                                               |
|---------------------------|------------------------------|-----------------------------------------------------|
| **Authoritative Audit**   | `maa_event_logging_authoritative_audit_outbox` | **Authoritative truth**, transactional, fail-closed |
|                           | `maa_event_logging_authoritative_audit_log`    | Materialized query table only                       |
| **Audit Trail**           | `maa_event_logging_audit_trail`               | Reads, views, exports, navigation                   |
| **Security Signals**      | `maa_event_logging_security_signals`          | Auth / policy anomalies                             |
| **Operational Activity**  | `maa_event_logging_behavior_trace`            | Mutations only                                      |
| **Diagnostics Telemetry** | `maa_event_logging_diagnostics_telemetry`     | Technical observability                             |
| **Delivery Operations**   | `maa_event_logging_delivery_operations`       | Jobs, queues, notifications                         |

**Hard rule:**
Tables are **semantically isolated**. Cross-domain writes are forbidden.

---

## 2) Baseline Retention (Policy Guidance)

Retention is **configuration-driven** and domain-specific.

Baseline operation does **NOT** require automated cleanup or archiving.

Recommended starting points (non-binding defaults):

| Domain                | Suggested Retention (days) | Rationale                            |
|-----------------------|---------------------------:|--------------------------------------|
| Audit Trail           |                         90 | High volume, frequent investigations |
| Security Signals      |                        180 | Security analysis & forensics        |
| Operational Activity  |                        180 | Accountability                       |
| Diagnostics Telemetry |                         30 | Short operational value              |
| Delivery Operations   |                        180 | Reliability & provider disputes      |

**Rule:** retention policy must be adjustable without schema changes.

---

## 3) Deferred Archiving Model — Mode B (MySQL → MySQL)

> **Status:** DEFERRED
> This is the **only supported archiving model**.

This document preserves the future archiving contract and safety constraints. It does not claim
that archive tables, retention workers, checkpoints, or hot/archive reads are implemented in the
current package Runtime. See `../DEFERRED_SCOPE.md` for the active deferred-scope boundary.

### 3.1 Why Mode B

* No dependency on unsupported non-MySQL storage
* Preserves column-based searchability
* Easy to review, migrate, or disable
* Aligns with portability and audit requirements

---

## 4) Archive Table Design (Mode B)

### 4.1 Archive Tables

For each hot table, a mirrored archive table MAY exist:

* `maa_event_logging_audit_trail_archive`
* `maa_event_logging_security_signals_archive`
* `maa_event_logging_behavior_trace_archive`
* `maa_event_logging_diagnostics_telemetry_archive`
* `maa_event_logging_delivery_operations_archive`
* *(Optional)* `maa_event_logging_authoritative_audit_log_archive`

**Rules:**

* Same columns as hot table
* Same critical indexes
* NO foreign keys
* NO behavioral logic

> **Important:**
> Even if archived, **Authoritative Audit truth remains**
> `maa_event_logging_authoritative_audit_outbox`.

---

## 5) Archiving Algorithm (Mode B)

Archiving is **Move + Delete inside MySQL**.

### 5.1 Stable Cursor (Hard Rule)

Ordering:

* `ORDER BY occurred_at ASC, id ASC`

Resume condition:

* `(occurred_at > :last_time)`
* OR `(occurred_at = :last_time AND id > :last_id)`

Eligibility:

* `occurred_at < :cutoff`

---

### 5.2 Required Checkpointing

Hard rule for a future archiver:
Checkpoint updates MUST be atomic with archive operations
to guarantee idempotency and crash safety.

Use the reserved table:

* `log_processing_checkpoints`

Fields:

* `log_stream` = hot table name
* `processor` = `archiver_mode_b`
* `last_processed_occurred_at`
* `last_processed_mysql_id`
* sanitized metadata (status, last_error)

---

### 5.3 Archiving Steps (Hard Rules)

For each eligible domain:

1. Compute cutoff using retention policy
2. Load checkpoint
3. Select batch from hot table (stable ordering)
4. Insert rows into corresponding `_archive` table
5. Verify row count equality
6. **Delete from hot table ONLY after successful insert**
7. Update checkpoint
8. Repeat until no eligible rows remain

**Hard safety rule:**
Deletion is **FORBIDDEN** unless archive insert succeeded.

---

## 6) Read Strategy (Hot + Archive)

If Mode B is enabled in a separately approved future implementation:

* Recent range → query hot table only
* Older range → query archive table only
* Mixed range → query both, merge by:

    * `occurred_at DESC`
    * stable cursor using `(occurred_at, id)`

---

## 7) Authoritative Audit & Archiving

Even if archive tables exist:

* **Authoritative truth** = `maa_event_logging_authoritative_audit_outbox`
* `maa_event_logging_authoritative_audit_log` and archive tables are **materialized views only**
* Loss of archive data MUST NOT affect governance correctness

---

## 8) Operational Safety Policies (Binding)

### 8.1 Metadata Handling (Domain Policy)

`metadata` size and handling follow the current policy and Runtime contract of each domain. This
storage document does not impose a global maximum or rejection rule.

For fail-open domains, oversized metadata MAY be sanitized, dropped, or replaced and recording may
continue according to the domain contract. This document does not invent size or rejection
behavior for an AuthoritativeAudit payload that the current Runtime does not define.

The future archiver copies stored records and does not redefine recorder metadata policy.

---

### 8.2 Timezone Policy

* `occurred_at` MUST be stored in **UTC**
* Conversion to local timezone is presentation-layer only

---

### 8.3 Retry & Failure Handling

* Archiver failures MUST be retriable
* Repeated failures MUST be visible via monitoring
* No silent data loss is allowed

---

## 9) Data Safety Rules (All Storage)

1. NEVER store secrets:

    * passwords
    * raw OTP codes
    * access tokens
    * session secrets
    * encryption keys

2. URL handling:

    * store path only
    * remove query strings
    * mask sensitive path segments if needed

3. PII minimization:

    * prefer identifiers or hashes
    * avoid raw personal data

4. Metadata discipline:

    * structured
    * minimal
    * allowlisted

---

## 10) Explicit Non-Goals

The following are **out of scope** for this architecture:

* MongoDB-based archiving
* Dual-write strategies
* MySQL partitioning
* Deleting Authoritative Audit records
* Implicit or silent archiving

---

## 11) Compliance Note

This storage and archiving model is designed to support:

* Security audits
* Compliance investigations
* GDPR-aligned retention and anonymization strategies
  *(defined outside this document)*

---

**End of Canonical Storage & Archiving Specification**
