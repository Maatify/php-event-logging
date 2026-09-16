# Changelog

All notable changes to `maatify/php-event-logging` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

The `1.0.0` entry below is a historical record of the legacy `maatify/event-logging` package. It does not represent a published release of `maatify/php-event-logging`.

## [Unreleased]

### Added
- Added Admin Query public API with package-owned request/result DTOs and MySQL repository for all six domains (DeliveryOperations, DiagnosticsTelemetry, AuthoritativeAudit, AuditTrail, BehaviorTrace, SecuritySignals).
- Added `maatify/persistence` for deterministic offset pagination mechanics.
- Added optional pure-PHP DI binding helper for host applications that want convenience container wiring without a mandatory DI dependency.

### Changed
- Raised the minimum supported PHP contract from `^8.2` to `^8.4` and aligned the Composer platform baseline and CI matrix.
- Extracted policy-aware row hydration into shared internal mappers while preserving primitive `find()` and `read()` behavior across domains.
- Corrected primitive `find()` cursor placeholders for native PDO prepared statements without changing cursor semantics across domains.
- Updated blueprints, package references, integration guides, and domain READMEs to reflect the current post-legacy-v1 Admin Query architecture.
- Polished Composer metadata (`composer.json`) to accurately reflect package scope, requirements, and dependencies.

### Removed
- Removed superseded pagination wrapper artifacts that were not protected contracts of the legacy `maatify/event-logging` `v1.0.0` compatibility baseline.

### Security
- Added `SECURITY.md` for explicit security policies and vulnerability reporting guidelines.

## [1.0.0] - 2026-07-06

### Summary
Initial stable release of `maatify/event-logging` under the strictly isolated `Maatify\EventLogging\` namespace. This is a framework-agnostic standalone Composer package with explicit dependencies (e.g., `ext-json`, `ext-pdo`, `psr/log`, `maatify/shared-common`, `maatify/exceptions`) and is intentionally not "dependency-free".

### Added
- Six rigorously isolated event logging domains: `AuthoritativeAudit`, `AuditTrail`, `SecuritySignals`, `BehaviorTrace`, `DiagnosticsTelemetry`, and `DeliveryOperations`.
- Domain-owned MySQL persistence using explicit PDO instances.
- Domain-specific recording models utilizing structured commands, recorders, and internal write DTO transfers.
- Primitive, deterministic read and query support via cursor-based MySQL query repositories, query DTOs, and view DTOs.
- Clear factory (`src/Factory/`) and provider (`src/Provider/`) layers for seamless, optional, framework-agnostic host application wiring.

### Changed
- Enforced strict alignment with Maatify core architecture, integrating `SystemMaatifyException` from `maatify/exceptions` for storage-related failures.
- Adopted `ClockInterface` exclusively from `maatify/shared-common` in place of internal implementations.

### Security
- Preserved AuthoritativeAudit fail-closed behavior, ensuring storage failures are surfaced instead of being swallowed or redirected to PSR-3 fallback logging.
- Structured non-authoritative domains to fail-open securely at the recorder boundary, with optional PSR-3 fallback logging capabilities.

### Validation
- Banned direct use of `RuntimeException` for storage/read/query exceptions, replacing them with strongly typed domain or system-level exceptions.

### Guarantees
- Supports MySQL-backed repositories only; SQLite is explicitly unsupported and must not be presented as a compatible runtime.
- Completely devoid of generic tables (e.g., `logs`, `event_logs`).
- Operates entirely free of framework-specific bindings and isolated from host application namespaces.
- Contains absolutely zero UI components, admin controllers, route handling, permissions logic, or generic analytics inside the package boundary.

[Unreleased]: https://github.com/Maatify/php-event-logging/compare/main...HEAD
[1.0.0]: https://github.com/Maatify/event-logging/releases/tag/v1.0.0
