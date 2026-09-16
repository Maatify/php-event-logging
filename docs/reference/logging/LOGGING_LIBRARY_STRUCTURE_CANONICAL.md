# LOGGING_LIBRARY_STRUCTURE_CANONICAL

> **Project:** maatify/php-event-logging
> **Status:** NON-BINDING (Future extraction and structural mapping)
> **Scope:** Preserves useful module boundaries and extraction mappings for the six logging domains without imposing future folders, classes, storage backends, or APIs on the current Runtime.
> **Terminology Source of Truth:** `../../architecture/logging/LOG_DOMAINS_OVERVIEW.md`
> **Design Standard Source of Truth:** `../../architecture/logging/CANONICAL_LOGGER_DESIGN_STANDARD.md`

---

## 0) Purpose

This document records a possible future extraction shape. It is not a current
Runtime contract and does not authorize extraction, new Composer dependencies,
new folders/classes, archive adapters, or API changes.

This prevents:

* “one mega logger”
* cross-domain coupling
* hidden policy in infrastructure
* ad-hoc DTO shapes replacing defined contracts

The current package remains one Composer library with six domain-isolated
modules. Any future standalone-library target requires separate Owner approval.

---

## 1) Canonical Domains (6)

The system has exactly six logging domains:

1. Authoritative Audit
2. Audit Trail
3. Security Signals
4. Operational Activity
5. Diagnostics Telemetry
6. Delivery Operations

Definitions are canonical in:

* `../../architecture/logging/LOG_DOMAINS_OVERVIEW.md`

---

## 2) Hard Structural Rules

1. **One module per domain.**
2. **No shared storage drivers across domains.**
3. **No domain policy inside infrastructure.**
4. **Ad-hoc associative arrays MUST NOT substitute for defined commands, DTOs,
   or contracts.** Domain-defined metadata arrays remain allowed where the
   current domain contract permits them.
5. All DTO class names MUST end with `DTO`.
6. All Enum names MUST end with `Enum`.
7. The five non-authoritative package recorders catch and swallow recording
   failures at their recorder boundary. An explicitly supplied PSR-3 logger
   MAY receive a diagnostic; no mandatory fallback channel exists when none is
   supplied. `AuthoritativeAuditRecorder` is fail-closed and must propagate
   recorder-boundary failures. Infrastructure, repositories, DTOs, and
   Policies MUST NOT swallow.

---

## 3) Illustrative Module Layout (Non-Binding)

All domain modules live under:

```
src/
```

Illustrative future extraction structure only (not required current Runtime):

```
src/<DomainName>/
Command/
Contract/
DTO/
Enum/
Recorder/
Infrastructure/
    Mysql/
Exception/

┌──────────────────────────────────────────────────────────────┐
│                      src/                            │
└──────────────────────────────────────────────────────────────┘
              |
              v
┌──────────────────────────────────────────────────────────────┐
│                 <DomainName> Module                          │
└──────────────────────────────────────────────────────────────┘
              |
              v
┌────────────┬────────────┬────────────┬────────────┬──────────┐
│ Contract/  │   DTO/     │   Enum/    │ Recorder/  │ Exception│
└────────────┴────────────┴────────────┴────────────┴──────────┘
              |
              v
┌──────────────────────────────────────────────────────────────┐
│                     Infrastructure/                          │
└──────────────────────────────────────────────────────────────┘
              |
              v
        ┌───────────────┬─────────────────────────────────────┐
        │    Mysql/     │       Future archive adapter        │
        │ (current)     │       (not specified/approved)      │
        └───────────────┴─────────────────────────────────────┘

        |
        v
┌───────────────────────────────────────────────────────────┐
│                    Exception/                             │
└───────────────────────────────────────────────────────────┘

```

Notes:

* Current persistence is MySQL only. MongoDB and other non-MySQL backends are
  unsupported.
* The approved deferred archive direction is MySQL → MySQL Mode B only; it is
  not implemented.
* The diagram does not require a future archive folder or class name.
* `Exception/` is a conceptual role for honest contracts; exact current
  locations follow the Runtime tree.

---

## 4) Domain Modules (Exact Names + Responsibility)

### 4.1 Authoritative Audit Module

**Domain intent:** Compliance-grade governance/security posture changes.
**Storage:** MySQL only (outbox + materialized audit log).

```
src/AuthoritativeAudit/
    Contract/
        AuthoritativeAuditOutboxWriterInterface.php
        AuthoritativeAuditLogReaderInterface.php
    DTO/
        AuthoritativeAuditOutboxWriteDTO.php
        AuthoritativeAuditLogViewDTO.php
    Enum/
        AuthoritativeAuditRiskLevelEnum.php
        AuthoritativeAuditActorTypeEnum.php
    Recorder/
        AuthoritativeAuditRecorder.php
    Infrastructure/
        Mysql/
            AuthoritativeAuditOutboxWriterMysqlRepository.php
            AuthoritativeAuditLogReaderMysqlRepository.php
    Exception/
        AuthoritativeAuditStorageException.php
        
┌──────────────────────────────────────────────────────────────┐
│        Authoritative Audit (Compliance-Grade Flow)           │
└──────────────────────────────────────────────────────────────┘
              |
              v
AuthoritativeAuditRecorder
              |
              v
AuthoritativeAuditOutboxWriterInterface
              |
              v
MySQL: maa_event_logging_authoritative_audit_outbox   [AUTHORITATIVE SOURCE]
              |
              v
Outbox Consumer / Materializer
              |
              v
MySQL: maa_event_logging_authoritative_audit_log      [QUERY / READ MODEL]


```

Hard rule:

* The outbox writer is part of the authoritative pipeline.
* No MongoDB archive is supported by the current Runtime.

---

### 4.2 Audit Trail Module

**Domain intent:** Data exposure + navigation (views/reads/exports/downloads).
**Storage:** Current Runtime MySQL only; any future archive remains deferred.

```
src/AuditTrail/
    Contract/
        AuditTrailLoggerInterface.php
        AuditTrailQueryInterface.php
    DTO/
        AuditTrailRecordDTO.php
        AuditTrailQueryDTO.php
        AuditTrailViewDTO.php
    Enum/
        AuditTrailActorTypeEnum.php
        AuditTrailEventKeyEnum.php (optional; taxonomy may be string-based if too broad)
    Recorder/
        AuditTrailRecorder.php
    Infrastructure/
        Mysql/
            AuditTrailLoggerMysqlRepository.php
            AuditTrailQueryMysqlRepository.php
    Exception/
        AuditTrailStorageException.php
```

Hard rule:

* Any view/read/export belongs here, never in Operational Activity.

---

### 4.3 Security Signals Module

**Domain intent:** Auth/authorization anomalies, policy violations, suspicious signals.
**Storage:** Current Runtime MySQL only; any future archive remains deferred.

```
src/SecuritySignals/
    Contract/
        SecuritySignalsLoggerInterface.php
        SecuritySignalsQueryInterface.php
    DTO/
        SecuritySignalRecordDTO.php
        SecuritySignalsQueryDTO.php
        SecuritySignalViewDTO.php
    Enum/
        SecuritySignalTypeEnum.php
        SecuritySignalSeverityEnum.php
        SecuritySignalActorTypeEnum.php
    Recorder/
        SecuritySignalsRecorder.php
    Infrastructure/
        Mysql/
            SecuritySignalsLoggerMysqlRepository.php
            SecuritySignalsQueryMysqlRepository.php
    Exception/
        SecuritySignalsStorageException.php
```

Hard rule:

* “Permission denied”, “login failed”, “session invalid” belong here.

---

### 4.4 Operational Activity Domain — BehaviorTrace Module

**Domain intent:** Mutations + operational actions (create/update/delete/approve/etc).
**Storage:** Current Runtime MySQL only; any future archive remains deferred.

> **Library / Module Name:** BehaviorTrace  
> **Domain Classification:** Operational Activity  
> **Authoritative Source:** ../../architecture/logging/LOG_DOMAINS_OVERVIEW.md

```
src/BehaviorTrace/
    Contract/
        BehaviorTraceLoggerInterface.php
        BehaviorTraceQueryInterface.php
    DTO/
        BehaviorTraceRecordDTO.php
        BehaviorTraceQueryDTO.php
        BehaviorTraceViewDTO.php
    Enum/
        BehaviorTraceActorTypeEnum.php
        BehaviorTraceActionEnum.php (optional; may be string taxonomy)
    Recorder/
        BehaviorTraceRecorder.php
    Infrastructure/
        Mysql/
            BehaviorTraceLoggerMysqlRepository.php
            BehaviorTraceQueryMysqlRepository.php
    Exception/
        BehaviorTraceStorageException.php
```

Hard rule:

* Reads/views/exports are forbidden here.

---

### 4.5 Diagnostics Telemetry Module

**Domain intent:** Technical observability (timings, sanitized errors, counters).
**Storage:** Current Runtime MySQL only; any future archive remains deferred.

```
src/DiagnosticsTelemetry/
    Contract/
        DiagnosticsTelemetryLoggerInterface.php
        DiagnosticsTelemetryQueryInterface.php
    DTO/
        DiagnosticsTelemetryRecordDTO.php
        DiagnosticsTelemetryQueryDTO.php
        DiagnosticsTelemetryViewDTO.php
    Enum/
        DiagnosticsTelemetrySeverityEnum.php
    Recorder/
        DiagnosticsTelemetryRecorder.php
    Infrastructure/
        Mysql/
            DiagnosticsTelemetryLoggerMysqlRepository.php
            DiagnosticsTelemetryQueryMysqlRepository.php
    Exception/
        DiagnosticsTelemetryStorageException.php
```

Hard rule:

* Must avoid PII/secrets.
* Never used for data access tracking.

---

### 4.6 Delivery Operations Module

**Domain intent:** Job/queue/notification/webhook lifecycle + retries + provider results.
**Storage:** Current Runtime MySQL only; any future archive remains deferred.

```
src/DeliveryOperations/
    Contract/
        DeliveryOperationsLoggerInterface.php
        DeliveryOperationsQueryInterface.php
    DTO/
        DeliveryOperationRecordDTO.php
        DeliveryOperationsQueryDTO.php
        DeliveryOperationViewDTO.php
    Enum/
        DeliveryChannelEnum.php
        DeliveryStatusEnum.php
        DeliveryOperationTypeEnum.php
        DeliverySeverityEnum.php (optional; if needed)
    Recorder/
        DeliveryOperationsRecorder.php
    Infrastructure/
        Mysql/
            DeliveryOperationsLoggerMysqlRepository.php
            DeliveryOperationsQueryMysqlRepository.php
    Exception/
        DeliveryOperationsStorageException.php
```

Hard rule:

* Only delivery lifecycle belongs here (queued/sent/failed/retry/provider ids).
* Not used for auth failures or data exposure.

---

## 5) Shared Primitives (Allowed Shared Module)

A small shared module is allowed ONLY for generic primitives that do not encode domain meaning:

```
src/LoggingCommon/
    Correlation/
        CorrelationId.php
        RequestId.php
        Sanitization/
        UrlSanitizer.php
        MetadataSanitizer.php
    Clock/


┌───────────────────────────────────────────────────────────┐
│                 Shared Primitives Boundary                │
└───────────────────────────────────────────────────────────┘
        |
        v
LoggingCommon
        |
        +───────────────+────────────────+─────────────+
        |               |                |             |
        v               v                v
Correlation/     Sanitization/        Clock/
(CorrelationId)  (Url/Metadata)       (Maatify\SharedCommon\Contracts\ClockInterface)

```
Important:
- There is no global ActorTypeEnum or global actor list in the current
  Runtime.
- `actor_type` normalization and validation are governed by each domain's
  current Policy and contract.
- Domain-specific actor classification must follow that domain contract.

Hard rule:

* LoggingCommon MUST NOT contain domain-specific rules.
* No storage code here.
* No “generic log event” DTO here.

---

## 6) Extraction Mapping (Future Library Targets)

This mapping records possible future extraction targets. It is non-binding:
the current package has no separate extracted packages, and no target folder,
class, backend, or API is authorized by this document. Separate Owner approval
is required before adopting any target architecture.

Each domain could map to a package:

* `maatify/authoritative-audit`
* `maatify/audit-trail`
* `maatify/security-signals`
* `maatify/operational-activity`
* `maatify/diagnostics-telemetry`
* `maatify/delivery-operations`
* `maatify/logging-common` (optional; keep minimal)

```

┌───────────────────────────────────────────────────────────┐
│               Extraction-Ready Package Mapping            │
└───────────────────────────────────────────────────────────┘
AuthoritativeAudit                      → maatify/authoritative-audit
AuditTrail                              → maatify/audit-trail
SecuritySignals                         → maatify/security-signals
OperationalActivity (BehaviorTrace)     → maatify/behavior-trace
DiagnosticsTelemetry                    → maatify/diagnostics-telemetry
DeliveryOperations                      → maatify/delivery-operations
LoggingCommon                           → maatify/logging-common

```

---

## 7) Minimum Interfaces (Canonical)

For a future extraction, each domain would be expected to define applicable
roles such as:

1. `Recorder` (public recording/coordinator boundary)
2. `Policy` (independent domain-specific normalization/validation role)
3. An applicable write contract and domain storage exception
4. An applicable primitive or Admin Query read contract where exposed

The current public recording surface may accept commands and primitive
convenience arguments through domain recorders. Writer/storage contracts use
their defined write DTOs. A public write API is not globally RecordDTO-only,
and domain-defined metadata arrays remain valid under their contracts.

DTO categories are not interchangeable: write/persistence DTOs,
query/request DTOs, page/result/view DTOs, and commands each follow their own
current boundary contract. Contract-defined value objects and nested DTOs may
be properties where the current contract defines them; any JsonSerializable
output must be JSON-safe according to that contract.

---

## Read-Side Corruption Tolerance (Explicit Exception)

- Query/Reader implementations MAY swallow JSON decode errors
  for `metadata` fields ONLY during read-mapping.
- In case of corruption, `metadata` MUST be set to `null`
  and the record MUST still be returned.
- No other swallowing is permitted on the read-side.

---

## 8) Enforcement Summary

A logging module violates this document if any of the following occurs:

* A domain module writes to another domain’s table/collection.
* A driver swallows exceptions.
* An ad-hoc array replaces a defined command, DTO, or contract.
* A recorder performs SQL or storage-adapter operations.
* Views/reads/exports are logged outside Audit Trail.
* Telemetry is used to represent business access events.

---

## 9) Explicit Non-Goals

This document does NOT define:
- Database schema details
- Retention periods
- Indexing strategies
- Query optimization rules
- Business-level logging decisions

Those are defined in their respective canonical documents.
