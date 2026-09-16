# Deferred Scope & Future Considerations

**Status:** CANONICAL — authoritative source for unimplemented and deferred scope
**Scope:** Documents capabilities, strategies, and implementation details that are deferred to future phases and are **not part of the inherited legacy `maatify/event-logging` `v1.0.0` runtime baseline**.

---

## 1. Archiving Strategy

While the unified logging architecture contemplates archiving to manage large data volumes, **archive support is deferred and optional**. It is not required for the correct functioning of the `maatify/php-event-logging` package.

**Deferred Archive Features:**
- Dedicated `*_archive` tables (e.g., `maa_event_logging_behavior_trace_archive`).
- MySQL-to-MySQL archival workers and checkpointing mechanisms.
- Retention policies (e.g., moving data older than 90 days to cold storage).
- "Hot + Archive" union read strategies for querying across active and archived records.

**Explicitly Unsupported:**
- MongoDB archiving is explicitly rejected and unsupported.

## 2. AuthoritativeAudit Outbox Materialization

The Authoritative Audit domain utilizes a transactional outbox (`maa_event_logging_authoritative_audit_outbox`) to ensure fail-closed guarantees during the host application's business transaction.

**Deferred Features:**
- Outbox consumer/materializer scripts.
- Dead-letter queue (DLQ) semantics or manual intervention flows for consumer failures.
- Cross-database synchronization via the outbox.

For the current baseline, the outbox acts as the authoritative source of truth, and moving data from the outbox to the `maa_event_logging_authoritative_audit_log` (schema/read model) is the responsibility of host-implemented consumer workers.

## 3. Reporting and Dashboard Phase 5

The current Runtime provides six domain-scoped Admin Query pagination APIs. It does not provide
reporting, aggregates, dashboard summaries, or a cross-domain reporting layer.

The following remain deferred and require a separate approved architecture and implementation:
- Domain-scoped reporting contracts for each of the six domains.
- Reporting summaries, trends, and aggregates.
- Dashboard summary contracts.
- Any cross-domain reporting query, unless separately authorized by an Owner decision.

This section is the authoritative deferred-scope record for reporting and dashboard work. Its
presence does not authorize implementation.

## 4. Host Application Responsibilities vs. Package Scope

The package is strictly an infrastructure library.

**Strictly Forbidden (Always Host Responsibility):**
The following capabilities must be implemented by the host application and will **never** be provided by this package:
- UI dashboards and frontend components.
- HTTP/API endpoints (routes, controllers, middleware).
- Access control and permissions for viewing logs.
- Host-specific search and reporting implementations (e.g., specific CSV exports or cross-table JOINs).

**Current, not deferred:**
Existing domain-scoped primitive read/query interfaces and the six domain-scoped Admin Query
pagination APIs are part of the current Runtime. The primitive APIs remain governed by the
`EVENT_LOGGING_PACKAGE_REFERENCE.md` and `PRIMITIVE_READ_QUERY_SUPPORT_DESIGN.md`; Admin Query
contracts remain governed by the approved Admin Query architecture and roadmap.

*Note on Admin Query API Dependency:*
The standardized pagination mechanics are used by the completed Admin Query implementations
through `maatify/persistence v1.1.0`. Future work listed here remains separately governed and
requires explicit Owner approval; this document does not authorize that work or a Stable release.
