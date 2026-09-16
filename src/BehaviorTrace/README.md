# BehaviorTrace Module (Operational Activity)

**Project:** maatify/php-event-logging
**Module:** BehaviorTrace
**Namespace:** `Maatify\EventLogging\BehaviorTrace`

## Purpose
This module provides a framework-agnostic, host-independent logging mechanism for **Operational Activity** (Mutations Only). It tracks "Who did what to what" (e.g., Create, Update, Delete actions). It is NOT for read/view logs (use Audit Trail) or technical logs (use Diagnostics Telemetry).

## Architecture

The module follows the Canonical Logger Design Standard:

1.  **Recorder** (`BehaviorTraceRecorder`): The recording, coordinating, and reliability boundary. It coordinates command handling, bounded field normalization, Policy calls, DTO creation, writer invocation, and fail-open behavior.
2.  **Contract** (`BehaviorTraceWriterInterface`): The interface for the storage driver.
3.  **DTOs**: Strict Data Transfer Objects for Context, Events, and Cursors.
4.  **Infrastructure** (`BehaviorTraceWriterMysqlRepository`): The MySQL implementation of the writer using PDO.
5.  **Policy** (`BehaviorTracePolicyInterface`): Interface for normalizing inputs (ActorType) and validating rules. A default implementation (`BehaviorTraceDefaultPolicy`) is provided.
6.  **Admin Query** (`BehaviorTraceAdminQueryInterface`): A separate offset-pagination API for host-owned administrative screens.

### Module Boundary / Public Surface

Consumers should strictly use the defined Public API:
- **Write:** `BehaviorTraceRecorder::record(...)`
- **Read:** `BehaviorTraceQueryInterface::read(...)`
- **Admin Query:** `BehaviorTraceAdminQueryInterface::paginate(...)`
- **Configure:** `BehaviorTracePolicyInterface` (optional implementation)

See `EVENT_LOGGING_PACKAGE_REFERENCE.md` for full contract details.

### Data Flow

```
Caller (Controller/Service)
  |
  v
Call BehaviorTraceRecorder::record(action, actorType, entityType, ...)
  |
  v
BehaviorTraceRecorder
  - Applies bounded field normalization (UTF-8 safe truncation)
  - Delegates actor normalization and metadata-size validation to Policy
  - Sanitizes nested sensitive metadata before size/encoding handling
  - Generates Event ID (UUID)
  - Constructs Context and Event DTOs
  |
  v
BehaviorTraceWriterInterface::write(DTO)
  |
  v
BehaviorTraceWriterMysqlRepository (Infrastructure)
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

The module requires the `maa_event_logging_behavior_trace` table. A canonical schema definition is provided within the module:

`src/BehaviorTrace/Database/schema.maa_event_logging_behavior_trace.sql`

This file should be used to initialize the database table.

## Usage

```php
use Maatify\EventLogging\BehaviorTrace\Recorder\BehaviorTraceRecorder;
use Maatify\EventLogging\BehaviorTrace\Enum\BehaviorTraceActorTypeEnum;
use Maatify\EventLogging\BehaviorTrace\Infrastructure\Mysql\BehaviorTraceWriterMysqlRepository;
use Maatify\SharedCommon\Infrastructure\SystemClock;

// Dependencies (usually injected; $pdo and $psrLogger are host-provided, and the logger is optional)
$writer = new BehaviorTraceWriterMysqlRepository($pdo);
$clock = new SystemClock(new \DateTimeZone('UTC'));
$recorder = new BehaviorTraceRecorder($writer, $clock, $psrLogger);

// Record Event
$recorder->record(
    action: 'user.create',
    actorType: BehaviorTraceActorTypeEnum::ADMIN, // or 'ADMIN'
    actorId: 123,
    entityType: 'user',
    entityId: 456,
    correlationId: 'abc-123',
    requestId: 'req-456',
    routeName: 'admin.user.store',
    ipAddress: '127.0.0.1',
    userAgent: 'Mozilla/5.0...',
    metadata: ['role' => 'editor']
);
```

### Failure Semantics (Best Effort)

The `BehaviorTraceRecorder` is designed to be **fail-open at the Recorder boundary**.
- Recording-flow failures are caught and swallowed by the Recorder, including failures during
  command handling, Policy normalization/validation, DTO construction, and writer execution.
- A diagnostic may be sent to the optional fallback `Psr\Log\LoggerInterface`; omitting that
  logger is valid.
- This ensures that a logging failure does not crash the main application request.

### Archiving Readiness

The module is designed to support future archiving via the `BehaviorTraceQueryInterface`.
- **Stable Cursors**: The `read()` method accepts a `BehaviorTraceCursorDTO` (last occurred_at + id) to allow reliable, stateless iteration.
- **Read-Side Resilience**: The reader handles unknown or invalid data gracefully.

### Admin Query

BehaviorTrace exposes a separate Admin Query API for host-owned administrative screens that need deterministic offset pagination:

```php
use Maatify\EventLogging\BehaviorTrace\DTO\BehaviorTraceAdminQueryRequestDTO;
use Maatify\EventLogging\BehaviorTrace\Infrastructure\Mysql\BehaviorTraceAdminQueryMysqlRepository;

// $pdo is a host-provided PDO instance.
$query = new BehaviorTraceAdminQueryMysqlRepository($pdo);

$page = $query->paginate(new BehaviorTraceAdminQueryRequestDTO(
    actorType: 'admin',
    actorId: 123,
    action: 'user.update',
    entityType: 'user',
    entityId: 456,
    page: 1,
    perPage: 20,
    sortBy: 'occurred_at',
    sortDirection: 'DESC'
));
```

Supported filters are `actorType`, `actorId`, `action`, `entityType`, `entityId`, `requestId`, `correlationId`, `after`, and `before`. Caller-selectable sorting is limited to `occurred_at`; `id` is reserved as the internal tie-breaker. The API returns `BehaviorTraceAdminPageResultDTO` with `items`, `page`, `perPage`, `total`, `filtered`, `totalPages`, `hasNext`, `hasPrevious`, `sortBy`, and `sortDirection`.

The primitive `find()` and `read()` methods remain protected compatibility contracts. Admin Query is additive and does not replace those cursor-based methods.

### Extensibility

- **ActorType**: Implement `BehaviorTraceActorTypeInterface`.
- **Policy**: Implement `BehaviorTracePolicyInterface` and inject it into the Recorder to change
  the domain-specific normalization/validation logic.

> **Reader Scope Clarification**
>
> The read-side provided by this module is a **primitive, cursor-based reader**
> intended strictly for archiving and sequential processing.
> Statements saying the reader is not designed for UI pagination, searching, or analytics apply only to this protected primitive reader inherited from legacy `maatify/event-logging` `v1.0.0`. BehaviorTrace now provides the separate Admin Query API for deterministic administrative offset pagination.

### Constraints & Guards

- **Timezone**: Dates are strictly enforced as UTC.
- **String Constraints**: The Recorder automatically truncates strings to fit database columns (e.g., `action` to 128, `user_agent` to 512).
- **Metadata**: MUST be an array or null. The Recorder structurally sanitizes nested sensitive
  associative keys before size/encoding handling and the writer boundary; maximum size is 64KB
  (JSON encoded).
- **Secrets**: Arbitrary free-text secret detection is not provided. Callers MUST NOT place raw
  passwords, tokens, or OTPs in metadata.
- **Actor Type**: Default policy enforces uppercase, max length 32, and sanitizes characters.
