# Admin Query API Architecture

**Status:** Approved Architecture / Current Runtime
**Phase:** Phase 4 Complete; Phase 5 Deferred

## 1. Purpose

This document defines the current architecture for the six domain-specific Admin Query APIs in
the `maatify/php-event-logging` package and the boundary for future reporting and dashboard work.

All `v1.0.0` references in this document refer to the inherited compatibility baseline from the legacy `maatify/event-logging` package. They do not indicate that `maatify/php-event-logging` has a published Stable release.

It applies to the additive post-legacy-v1 Admin Query path and must be read together with:

- [ADMIN_QUERY_API_ROADMAP.md](../roadmap/ADMIN_QUERY_API_ROADMAP.md)
- [ADMIN_QUERY_PHASE_1_RUNTIME_COMPATIBILITY_INVENTORY.md](../audits/ADMIN_QUERY_PHASE_1_RUNTIME_COMPATIBILITY_INVENTORY.md)
- [EVENT_LOGGING_PACKAGE_REFERENCE.md](../../EVENT_LOGGING_PACKAGE_REFERENCE.md)
- [PRIMITIVE_READ_QUERY_SUPPORT_DESIGN.md](PRIMITIVE_READ_QUERY_SUPPORT_DESIGN.md)

The architecture separates three distinct layers:

1. The inherited and protected legacy `maatify/event-logging` `v1.0.0` Runtime compatibility baseline.
2. The current package-owned, domain-specific Admin Query APIs.
3. Future reporting and dashboard contracts, which are not implemented.

No implementation is authorized by this document alone.

## 2. Protected Legacy `v1.0.0` Runtime Compatibility Baseline

The legacy `maatify/event-logging` `v1.0.0` release remains the canonical compatibility and Runtime baseline inherited by this repository.

The following are frozen and outside the remediation scope of this architecture:

- Event-writing and logging Runtime behavior.
- Public domain contracts released in legacy `maatify/event-logging` `v1.0.0`.
- Primitive read/query APIs across all six logging domains.
- Existing query DTOs and view/event DTOs.
- Existing repositories, schemas, row hydration behavior, and domain exceptions.
- Existing cursor behavior that belongs to the legacy first-release Runtime.
- Existing host integrations that depend on legacy first-release contracts.

No Admin pagination or reporting phase may redesign, replace, remove, or silently alter this baseline.

Any internal refactor required by later work must prove through regression coverage that all legacy first-release behavior remains identical.

## 3. Superseded Post-Legacy-v1 Pagination Experiment

The post-legacy-v1 pagination wrapper family in four domains was an implementation experiment,
not part of the protected legacy `maatify/event-logging` `v1.0.0` baseline:

- `AuditTrail`
- `BehaviorTrace`
- `SecuritySignals`
- `AuthoritativeAudit`

These artifacts included combinations of:

- `*PaginatedQueryInterface`
- cursor/page DTOs
- `*PaginatedQueryService`

The wrapper family is not current API and is not a compatibility target. It must not be
generalized or copied to additional domains. The completed replacement implementations use the
current Admin Query architecture, and the superseded artifacts were retired only with their
replacement and compatibility gates. Retiring them does not affect any protected legacy
contract or Runtime behavior.

## 4. Current Admin Query API

The Admin Query API is a separate, offset/page-based execution path for host-owned
administrative screens that need domain-specific filtering, sorting, and deterministic pagination.

It does not replace the primitive Runtime.

It is implemented for all six domains:

- `AuditTrail`
- `BehaviorTrace`
- `SecuritySignals`
- `AuthoritativeAudit`
- `DiagnosticsTelemetry`
- `DeliveryOperations`

The six domains must not be implemented as one bulk generic repository or one cross-domain query layer.

Each domain requires its own reviewed contract, filter rules, trusted SQL, mapper, exception policy, tests, and documentation.

## 5. Strict Boundary Responsibilities

### 5.1 Event Logging Package (`maatify/php-event-logging`)

The package owns all domain-specific behavior:

- Mandatory domain constraints.
- Security, ownership, tenant, and visibility constraints where applicable.
- Domain search and filter construction.
- Domain JOINs and selected columns where explicitly approved.
- Trusted SQL and matching parameters.
- Approved public sort keys and trusted SQL mappings.
- Semantic alignment between total-count, filtered-count, and data queries.
- Row mapping into package/domain DTOs.
- Package-owned request and response contracts.
- Translation of persistence exceptions into the approved package exception boundary.
- Regression protection for all Runtime behavior protected by legacy `maatify/event-logging` `v1.0.0`.

### 5.2 Pagination Owner (`maatify/persistence v1.1.0+`)

`maatify/persistence` exclusively owns generic pagination mechanics:

- Page and per-page normalization.
- Total and filtered count execution.
- Offset calculation.
- Deterministic sorting execution.
- Sort whitelist enforcement.
- Tie-breaker behavior.
- `LIMIT` and `OFFSET` handling.
- Mapper invocation.
- Canonical pagination metadata.
- Generic pagination query validation and execution errors.

The event-logging package must not copy, fork, or reimplement these mechanics.

### 5.3 Host Application

The host application retains responsibility for integration concerns:

- HTTP controllers and routes.
- Permissions and user authorization.
- Actor, target, entity, or subject name resolution.
- Localization.
- UI screens and tables.
- HTTP response mapping.
- Exports.

These concerns must not be moved into the package.

## 6. Count and Data Semantic Alignment

For every domain implementation:

- `total` counts rows under mandatory package/domain constraints without optional Admin filters.
- `filtered` counts rows under the same mandatory constraints plus all accepted Admin filters.
- The data query uses exactly the same mandatory and optional filter semantics as `filtered`.
- Filtered-count and data SQL must be generated from one shared semantic source of truth.
- Null handling, date boundaries, type/id pairs, parameter normalization, and security constraints must not diverge between count and data queries.

No current count/data alignment is assumed merely because primitive cursor queries already exist.

Every domain must prove alignment through focused unit and integration tests.

## 7. Sorting and SQL Safety

Each domain must define an explicit public sort whitelist.

The implementation must:

- Map public sort keys to trusted SQL identifiers.
- Reject or normalize invalid sort input according to the approved contract.
- Define a deterministic default sort.
- Define a deterministic tie-breaker.
- Never accept arbitrary column names.
- Never include paginator-owned `ORDER BY`, `LIMIT`, or `OFFSET` inside the supplied data SQL.
- Follow all `PdoPaginationQueryDescriptor` SQL and parameter restrictions.
- Use explicit selected columns rather than `SELECT *` for the new Admin path.

## 8. Reporting and Dashboard Contracts — Future / Not Implemented

Reporting and dashboard summary work is a separate future Phase 5 and is not implemented by the
current Runtime.

It begins only after pagination is complete and stable across all six domains.

Reporting must cover all six domains, domain by domain, and may include:

- Counts.
- Trends.
- Domain-specific aggregates.
- Dashboard summary contracts.

Reporting work must not be mixed into pagination remediation PRs.

Cross-domain reporting queries remain prohibited unless a separate approved architecture decision explicitly authorizes them.

## 9. Current Contract Precedence

- [EVENT_LOGGING_PACKAGE_REFERENCE.md](../../EVENT_LOGGING_PACKAGE_REFERENCE.md) remains the canonical current Runtime and public API contract.
- [PRIMITIVE_READ_QUERY_SUPPORT_DESIGN.md](PRIMITIVE_READ_QUERY_SUPPORT_DESIGN.md) remains authoritative for the legacy first-release primitive query path.
- [ADMIN_QUERY_API_ROADMAP.md](../roadmap/ADMIN_QUERY_API_ROADMAP.md) defines the approved post-legacy-v1.0 execution order.
- [ADMIN_QUERY_PHASE_1_RUNTIME_COMPATIBILITY_INVENTORY.md](../audits/ADMIN_QUERY_PHASE_1_RUNTIME_COMPATIBILITY_INVENTORY.md) defines the historical Phase 1 baseline and retained compatibility evidence. It is not current Runtime authority.
- Current per-domain Runtime truth is established by the current `main` implementation and [EVENT_LOGGING_PACKAGE_REFERENCE.md](../../EVENT_LOGGING_PACKAGE_REFERENCE.md).
- The current Runtime implementation has delivered all six Admin Query paths. A future package
  release remains separately governed and is not implied by this development state.

## 10. Absolute Prohibitions

The following are prohibited:

- Modifying or replacing the inherited legacy `maatify/event-logging` `v1.0.0` Runtime baseline as part of pagination remediation.
- Treating incorrect post-legacy-v1.0 pagination artifacts as protected legacy first-release contracts.
- Extending the old wrapper experiment to `DiagnosticsTelemetry` or `DeliveryOperations`.
- Creating one generic repository for all domains.
- Creating generic cross-domain queries.
- Adding HTTP, UI, permission, localization, or export code to this package.
- Copying pagination mechanics from `maatify/persistence`.
- Starting reporting work before all six Admin pagination paths are complete.
- Skipping any domain from the final pagination or reporting coverage.

## 11. Current State and Future Boundary

- Phase 4 Admin Query pagination is complete across all six domains.
- The primitive cursor-based path remains protected and separate.
- Phase 5 reporting and dashboard summary work is future and not implemented.
- Phase 6 host integration documentation and validation remains separate from this package's
  current Runtime contracts.

The roadmap records sequencing and status; this document records the current architecture. No
implementation, release, or tag is authorized by this section.

## 12. Implementation Gate

Phase 3 Remediation Complete.
Phase 4 Complete.
All six Admin Query Runtime implementations complete.
Reporting/dashboard remains blocked pending separate Phase 5 approval and implementation.
No release or tag authorized

- `AuditTrail`: Runtime implemented.
- `BehaviorTrace`: Runtime implemented.
- `SecuritySignals`: Runtime implemented.
- `AuthoritativeAudit`: Runtime implemented.
- `DiagnosticsTelemetry`: Runtime implemented
- `DeliveryOperations`: Runtime implemented.

Approval of this architecture document alone does not authorize Composer, Runtime, schema, test, tag, or release changes.
