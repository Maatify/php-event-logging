# LOGGING_ASCII_OVERVIEW

> **Project:** maatify/php-event-logging
> **Status:** NON-BINDING (ASCII overview of unified logging architecture)
> **Legend Source of Truth:** [`ASCII_FLOW_LEGENDS.md`](ASCII_FLOW_LEGENDS.md)
> **Terminology Source of Truth:** `../../architecture/logging/LOG_DOMAINS_OVERVIEW.md`
> **Storage Source of Truth:** `../../architecture/logging/LOG_STORAGE_AND_ARCHIVING.md`

---

## 0) What This Document Is (And Is Not)

This file is an **ASCII-only visual overview** of the unified logging system.

* It shows **flow shapes**, **responsibility boundaries**, and **storage topology**
* It does NOT redefine terminology or storage rules
* It acts as a **visual index** tying all canonical logging documents together

If any mismatch is found between this file and:

* `ASCII_FLOW_LEGENDS.md`
* `../../architecture/logging/LOG_DOMAINS_OVERVIEW.md`
* `../../architecture/logging/LOG_STORAGE_AND_ARCHIVING.md`
* `../../architecture/logging/GLOBAL_LOGGING_RULES.md`
* `../../architecture/logging/UNIFIED_LOGGING_DESIGN.md`

Then this file MUST be updated to match them.

---

## 1) Unified Logging Pipeline (All Domains)

* Failure semantics:
  - Authoritative Audit: fail-closed (transactional outbox)
  - AuditTrail, SecuritySignals, BehaviorTrace / Operational Activity,
    DiagnosticsTelemetry, and DeliveryOperations: fail-open at the package
    Recorder boundary
  - A supplied PSR-3 logger MAY receive a diagnostic for a non-authoritative
    failure; no mandatory fallback channel exists when none is supplied

Each logging domain follows the same high-level pipeline shape.
Only **policy strictness** and **failure semantics** differ by domain.

```

┌──────────────────────────────────────┐
│           HTTP / UI Layer            │
│   Controllers / Middleware / Routes  │
└─────────────────────┬────────────────┘
                      │
                      v
┌──────────────────────────────────────┐
│        Domain Recorder Layer         │
│  Policy delegation + DTO/context assembly │
└─────────────────────┬────────────────┘
                      │
                      v
┌──────────────────────────────────────┐
│       Domain Logger / Writer         │
│   Storage Adapter (Interface/Impl)   │
└─────────────────────┬────────────────┘
                      │
                      v
┌──────────────────────────────────────┐
│            Storage Layer             │
│   MySQL only; domain-isolated        │
│   Current tables use maa_event_logging_* │
└──────────────────────────────────────┘

```

**Canonical notes:**

* Recorder is the public recording/coordinator boundary.
* Policy is an independent domain-specific normalization/validation component.
* Logger/Writer is storage-only and follows the domain contract.
* No controller or service writes logs directly
* No domain mixes with another

---

## 2) Domains (6) and Storage Targets

Canonical meanings are defined in `../../architecture/logging/LOG_DOMAINS_OVERVIEW.md`.

### 2.1 Authoritative Audit (MySQL ONLY)

* Governance & security posture changes
* Fail-closed
* Authoritative pipeline (outbox → materialized log)
* The outbox is the authoritative source of truth.
* The materialized log is the read model used for reads.
* No archive backend is implemented.

```
┌───────────────────────────────┐
│       Authoritative Audit     │
└───────────────┬───────────────┘
                │
                v
┌──────────────────────────────────────┐
│ MySQL: maa_event_logging_authoritative_audit_outbox │
└─────────────────────┬────────────────┘
                      │
                      v
┌──────────────────────────────────────┐
│     Outbox Consumer / Materializer   │
└─────────────────────┬────────────────┘
                      │
                      v
┌──────────────────────────────────────┐
│ MySQL: maa_event_logging_authoritative_audit_log    │
└──────────────────────────────────────┘

```

---

### 2.2 Future Archive Scope (5 Domains; Not Implemented)

These domains currently persist to isolated MySQL tables:

* Audit Trail
* Security Signals
* Operational Activity
* Diagnostics Telemetry
* Delivery Operations

The only approved future archive direction is MySQL → MySQL Mode B, as
described in `../../architecture/logging/LOG_STORAGE_AND_ARCHIVING.md`.
It is deferred and not part of the current Runtime. MongoDB and a current Mode
A are unsupported, not alternate visual implementations.

```

┌───────────────────────────────┐
│ Current domain-isolated MySQL │
│ table(s)                       │
└───────────────┬───────────────┘
                │
                │ future, deferred Mode B only
                v
┌───────────────────────────────┐
│ Future MySQL archive contract  │
│ (separate approval required)   │
└───────────────────────────────┘

```

**Hard rule:**
No current Runtime path may be inferred from this future diagram. A future
archiver must preserve domain contracts and may not redefine recorder metadata
or failure policy.

---

## 3) Detailed Domain Flow Maps

### 3.1 Audit Trail (Data Exposure & Navigation)

```

HTTP / UI
   │
   v
AuditTrailRecorder
   │
   v
AuditTrailLogger
   │
   └──▶ MySQL : maa_event_logging_audit_trail

```

---

### 3.2 Security Signals

```

HTTP / UI + Domain Services
   │
   v
SecuritySignalsRecorder
   │
   v
SecuritySignalsLogger
   │
   └──▶ MySQL : maa_event_logging_security_signals

```

---

### 3.3 Operational Activity (Mutations Only)

```

HTTP / UI
   │
   v
OperationalActivityRecorder
   │
   v
OperationalActivityLogger
   │
   └──▶ MySQL : maa_event_logging_behavior_trace

```

---

### 3.4 Diagnostics Telemetry (Tech Observability)

```

Middleware / Instrumentation / HTTP
   │
   v
DiagnosticsTelemetryRecorder
   │
   v
DiagnosticsTelemetryLogger
   │
   └──▶ MySQL : maa_event_logging_diagnostics_telemetry

```

---

### 3.5 Delivery Operations (Jobs / Notifications / Webhooks)

```

Queue / Job / Notifier
   │
   v
DeliveryOperationsRecorder
   │
   v
DeliveryOperationsLogger
   │
   └──▶ MySQL : maa_event_logging_delivery_operations

```

---

### 3.6 Authoritative Audit (Compliance-Grade)

```

Domain Policy (Governance / Posture Change)
   │
   v
AuthoritativeAuditRecorder
   │
   v
Outbox Writer
   │
   v
MySQL : maa_event_logging_authoritative_audit_outbox
   │
   v
Outbox Consumer / Materializer
   │
   v
MySQL : maa_event_logging_authoritative_audit_log

```

---

## 4) Read Strategy Overview

### 4.1 Baseline (No Archiving Enabled)

```
Request Range
|
v
MySQL (current domain-isolated tables only)
|
v
Response
```

---

### 4.2 Future Mode B (MySQL → MySQL; Deferred)

```

Request Range
   │
   ├──▶ Current MySQL read model
   │
   └──▶ Future MySQL archive read path (deferred)

```

---

## 5) Real-World Mapping Appendix (Non-Blocking, Clarifying)

This appendix exists to **reduce ambiguity for reviewers and new developers**.
It does NOT introduce new rules.

### Example A — `login_failed`

* **Domain:** Security Signals
* **Why:** Observational auth anomaly
* **NOT:** Authoritative Audit (no posture change)

---

### Example B — `create_admin`

This is **TWO distinct events**:

1. **Authoritative Audit**

   * Intent: governance / privileged account creation
2. **Operational Activity**

   * Intent: operational record of entity creation

They MUST be logged as **two separate events**, never merged.

---

### Example C — `export_customer_report`

* **Domain:** Audit Trail
* **Why:** Data exposure
* **NOT:** Operational Activity
* **NOT:** Diagnostics Telemetry

---

## 6) Glossary (Canonical Clarification)

### Audit Trail vs Authoritative Audit

* **Audit Trail**

   * Answers: *Who saw what?*
   * Concern: data exposure
   * Non-authoritative
   * Reads / views / exports

* **Authoritative Audit**

   * Answers: *What changed governance or security posture?*
   * Concern: compliance & authority
   * Authoritative source of truth
   * Mutations with legal / security weight

---

### Security Signals vs Operational Activity

* **Security Signals**

   * Observations, denials, failures
   * Best-effort
   * Never changes system state

* **Operational Activity**

   * Successful mutations
   * Day-to-day admin operations
   * No reads, no failures

---

## 7) Visual Hard Prohibitions (Reminder)

```

Diagnostics Telemetry  ───▶ Authoritative Audit tables   (FORBIDDEN)
Operational Activity   ───▶ Views / Reads / Exports      (FORBIDDEN)
Audit Trail            ───▶ Mutations                    (FORBIDDEN)
Infrastructure         ───▶ swallow                      (FORBIDDEN)
Same intent            ───▶ Multiple domains             (FORBIDDEN)

```

---

## 8) Canonical Closing Statement

This file is a **visual index**, not a rulebook.

> If a rule is not defined in the source documents,
> it does not gain authority by appearing here.

All authority remains with:

* `../../architecture/logging/LOG_DOMAINS_OVERVIEW.md`
* `../../architecture/logging/GLOBAL_LOGGING_RULES.md`
* `../../architecture/logging/UNIFIED_LOGGING_DESIGN.md`
* `../../architecture/logging/LOG_STORAGE_AND_ARCHIVING.md`

**END OF FILE**
