# Diagnostics Telemetry Module

**Project:** maatify/php-event-logging
**Module:** DiagnosticsTelemetry
**Namespace:** `Maatify\EventLogging\DiagnosticsTelemetry`

## Purpose
This module provides a framework-agnostic, host-independent logging mechanism for **Diagnostics Telemetry** ONLY. It is designed to be the simplest starting point for a unified logging architecture.

## Architecture

The module follows the Canonical Logger Design Standard:

1.  **Recorder** (`DiagnosticsTelemetryRecorder`): The recording, coordinating, and reliability boundary. It coordinates command handling, bounded field normalization, Policy calls, DTO creation, writer invocation, and fail-open behavior.
2.  **Contract** (`DiagnosticsTelemetryLoggerInterface`): The interface for the storage driver.
3.  **DTOs**: Strict Data Transfer Objects for Context, Events, and Cursors. DTOs depend on Extensible Interfaces.
4.  **Infrastructure** (`DiagnosticsTelemetryLoggerMysqlRepository`): The MySQL implementation of the writer using PDO.
5.  **Policy** (`DiagnosticsTelemetryPolicyInterface`): Interface for normalizing inputs (Severity, ActorType) and validating rules. A default implementation (`DiagnosticsTelemetryDefaultPolicy`) is provided.

### Module Boundary / Public Surface

Consumers should strictly use the defined Public API:
- **Write:** `DiagnosticsTelemetryRecorder::record(...)`
- **Read (Primitive Archive/Cursor):** `DiagnosticsTelemetryQueryInterface::read(...)` and `DiagnosticsTelemetryQueryInterface::find(...)`
- **Read (Admin Pagination):** `DiagnosticsTelemetryAdminQueryInterface::paginate(...)`
- **Configure:** `DiagnosticsTelemetryPolicyInterface` (optional implementation)

See `EVENT_LOGGING_PACKAGE_REFERENCE.md` for full contract details.

### Data Flow

```
Caller (Controller/Service)
  |
  v
Call DiagnosticsTelemetryRecorder::record(eventKey, severity, actorType, ...)
  |
  v
DiagnosticsTelemetryRecorder
  - Applies bounded field normalization (UTF-8 safe truncation)
  - Normalizes Duration (>= 0)
  - Delegates actor/severity normalization and metadata-size validation to Policy
  - Sanitizes nested sensitive metadata before size/encoding handling
  - Generates Event ID (UUID)
  - Constructs Context and Event DTOs
  |
  v
DiagnosticsTelemetryLoggerInterface::write(DTO)
  |
  v
DiagnosticsTelemetryLoggerMysqlRepository (Infrastructure)
  - Serializes Metadata (JSON)
  - Formats Dates (UTC)
  - Executes INSERT SQL
```

### Dependency Flow

The module is designed to be isolated.
- **Inbound**: Caller depends on `Recorder`, `Enum Interfaces`.
- **Outbound**: Module depends only on:
    - `PDO` (standard PHP extension)
    - `Psr\Log\LoggerInterface` (standard PSR)
    - `Ramsey\Uuid` (explicit dependency for UUIDv4 generation)
    - `Maatify\SharedCommon\Contracts\ClockInterface`



## Database Schema

The module requires the `maa_event_logging_diagnostics_telemetry` table. A canonical schema definition is provided within the module:

`src/DiagnosticsTelemetry/Database/schema.maa_event_logging_diagnostics_telemetry.sql`

This file should be used to initialize the database table.

## Usage

```php
use Maatify\EventLogging\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder;
use Maatify\EventLogging\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityEnum;
use Maatify\EventLogging\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeEnum;
use Maatify\EventLogging\DiagnosticsTelemetry\Infrastructure\Mysql\DiagnosticsTelemetryLoggerMysqlRepository;
use Maatify\SharedCommon\Infrastructure\SystemClock;

// Dependencies (usually injected; $pdo and $psrLogger are host-provided, and the logger is optional)
$writer = new DiagnosticsTelemetryLoggerMysqlRepository($pdo);
$clock = new SystemClock(new \DateTimeZone('UTC'));
$recorder = new DiagnosticsTelemetryRecorder($writer, $clock, $psrLogger);

// Record Event (Pass scalars or Enums)
$recorder->record(
    eventKey: 'http.request',
    severity: DiagnosticsTelemetrySeverityEnum::INFO, // or 'INFO'
    actorType: DiagnosticsTelemetryActorTypeEnum::USER, // or 'USER'
    actorId: 123,
    correlationId: 'abc-123',
    requestId: 'req-456',
    routeName: 'api.test',
    ipAddress: '127.0.0.1',
    userAgent: 'Mozilla/5.0...',
    durationMs: 45,
    metadata: ['url' => '/api/test']
);
```

### Failure Semantics (Best Effort)

The `DiagnosticsTelemetryRecorder` is designed to be **fail-open at the Recorder boundary**.
- Recording-flow failures are caught and swallowed by the Recorder, including failures during
  command handling, Policy normalization/validation, DTO construction, and writer execution.
- A diagnostic may be sent to the optional fallback `Psr\Log\LoggerInterface`; omitting that
  logger is valid.
- This ensures that a telemetry logging failure does not crash the main application request.

### Archiving Readiness

The module is designed to support future archiving via the `DiagnosticsTelemetryQueryInterface`.
- **Stable Cursors**: The `read()` method accepts a `DiagnosticsTelemetryCursorDTO` (last occurred_at + id) to allow reliable, stateless iteration over large datasets (e.g. for moving old logs to an archive).
- **Read-Side Resilience**: The reader handles unknown or invalid data (e.g. from an old version of the app or manual DB edit) gracefully by sanitizing it into valid DTOs.

### Extensibility

- **Severity**: Implement `DiagnosticsTelemetrySeverityInterface`.
- **ActorType**: Implement `DiagnosticsTelemetryActorTypeInterface`.
- **Policy**: Implement `DiagnosticsTelemetryPolicyInterface` and inject it into the Recorder to
  change the domain-specific normalization/validation logic (e.g., allowed actor types, regex patterns).

> **Reader Scope Clarification**
>
> The protected primitive read-side compatibility contract inherited from legacy `maatify/event-logging` `v1.0.0` and provided by this module (`DiagnosticsTelemetryQueryInterface`) is a **primitive, cursor-based reader** intended strictly for archiving and sequential processing.
>
> For administrative UI dashboards, the package provides a separate offset-based Admin Query API (`DiagnosticsTelemetryAdminQueryInterface`). The Admin Query API supports filtering by actor, event key, severity, request ID, correlation ID, and date range. The Admin Query API uses strict deterministic sorting by `occurred_at DESC` and `id DESC`. Note that generic search, free-text search, event ID lookup, and complex reporting aggregations remain explicitly out of scope for the package.


### Constraints & Guards

- **Timezone**: Dates are strictly enforced as UTC.
- **String Constraints**: The Recorder automatically truncates strings to fit database columns (e.g., `event_key` to 255, `user_agent` to 512) using UTF-8 safe truncation (if `mbstring` is available).
- **Duration**: `duration_ms` is automatically coerced to 0 if negative.
- **Metadata**: MUST be an array or null. The Recorder structurally sanitizes nested sensitive
  associative keys before size/encoding handling and the writer boundary; maximum size is 64KB
  (JSON encoded).
- **Secrets**: Arbitrary free-text secret detection is not provided. Callers MUST NOT place raw
  passwords, tokens, or OTPs in metadata.
- **Actor Type**: Default policy enforces uppercase, max length 32, and sanitizes characters (replacing invalid chars with `_`) using pattern `[^A-Z0-9_.:-]`. It does NOT collapse invalid types to ANONYMOUS by default, but sanitizes them to valid ad-hoc types. Falls back to ANONYMOUS if sanitization results in an empty string.

## Admin Query API

The DiagnosticsTelemetry Admin Query API provides paginated admin read access using `maatify/persistence`.

### Supported Filters
- `actorType` (independent)
- `actorId` (independent)
- `eventKey`
- `severity`
- `requestId`
- `correlationId`
- `after`
- `before`

### Pagination and Results
Page results strictly guarantee the serialization order: `items`, `page`, `perPage`, `total`, `filtered`, `totalPages`, `hasNext`, `hasPrevious`, `sortBy`, `sortDirection`.

### Exception Boundaries
- Validation errors map to `DiagnosticsTelemetryAdminQueryInvalidArgumentException`.
- Admin execution/pagination errors map to `DiagnosticsTelemetryAdminQueryExecutionException`.
- Native mapping and database execution errors map to `DiagnosticsTelemetryStorageException`.

### Unsupported Features
The Admin Query API explicitly excludes:
- `eventId`, `routeName`, and `durationMs` filtering
- Metadata search or free-text search
- Generic filtering and arbitrary SQL injection

The host application retains ownership of authorization, controllers, routes, UI, localization, actor resolution, exports, and presentation.

*Note: The Admin API is a dedicated administrative access path and does not replace the `DiagnosticsTelemetryQueryInterface::find()` or legacy `read()` primitive patterns.*
