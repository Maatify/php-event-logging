# DeliveryOperations Module

**Project:** maatify/php-event-logging
**Module:** DeliveryOperations
**Namespace:** `Maatify\EventLogging\DeliveryOperations`

## Purpose
This module provides a framework-agnostic, host-independent logging mechanism for **Delivery Operations** (Jobs, Queues, Notifications, Webhooks). It tracks the lifecycle of asynchronous operations (e.g., queued, sent, delivered, failed).

**Key Characteristics:**
- **Best-Effort:** Logging failures are swallowed (fail-open) to prevent disrupting the core operation.
- **Fail-Open:** Recording-flow failures are swallowed at the Recorder boundary. An optional
  fallback logger may receive a diagnostic, but its absence is valid.

## Architecture

The module follows the Canonical Logger Design Standard:

1.  **Recorder** (`DeliveryOperationsRecorder`): The recording, coordinating, and reliability boundary. It coordinates command handling, bounded field normalization, Policy calls, DTO creation, writer invocation, and fail-open behavior.
2.  **Contract** (`DeliveryOperationsLoggerInterface`): The interface for the storage driver.
3.  **DTOs**: Strict Data Transfer Objects for Write.
4.  **Infrastructure** (`DeliveryOperationsLoggerMysqlRepository`): The MySQL implementation of the writer using PDO.
5.  **Policy** (`DeliveryOperationsPolicyInterface`): Interface for normalizing inputs. A default implementation (`DeliveryOperationsDefaultPolicy`) is provided.

### Module Boundary / Public Surface

Consumers should strictly use the defined Public API:
- **Write:** `DeliveryOperationsRecorder::record(...)`
- **Configure:** `DeliveryOperationsPolicyInterface`
- **Read (Primitive):** `DeliveryOperationsQueryInterface::find(...)`
- **Read (Admin Query):** `DeliveryOperationsAdminQueryInterface::paginate(...)`

### Data Flow

```
Caller (Job/Service)
  |
  v
Call DeliveryOperationsRecorder::record(...)
  |
  v
DeliveryOperationsRecorder
  - Applies bounded field normalization
  - Delegates actor normalization and metadata-size validation to Policy
  - Normalizes operation enums (Channel, Status, Type)
  - Sanitizes nested sensitive metadata before size/encoding handling
  - Validates Metadata Size
  - Constructs DTO
  |
  v
DeliveryOperationsLoggerInterface::log(DTO)
  |
  v
DeliveryOperationsLoggerMysqlRepository (Infrastructure)
  - Serializes Metadata (JSON)
  - Formats Dates (UTC)
  - Executes INSERT SQL (maa_event_logging_delivery_operations)
```

## Database Schema

The module requires the `maa_event_logging_delivery_operations` table. A canonical schema definition is provided within the module:

`src/DeliveryOperations/Database/schema.maa_event_logging_delivery_operations.sql`

This file should be used to initialize the database table.

## Usage

```php
use Maatify\EventLogging\DeliveryOperations\Recorder\DeliveryOperationsRecorder;
use Maatify\EventLogging\DeliveryOperations\Enum\DeliveryChannelEnum;
use Maatify\EventLogging\DeliveryOperations\Enum\DeliveryStatusEnum;
use Maatify\EventLogging\DeliveryOperations\Enum\DeliveryOperationTypeEnum;
use Maatify\EventLogging\DeliveryOperations\Infrastructure\Mysql\DeliveryOperationsLoggerMysqlRepository;
use Maatify\SharedCommon\Infrastructure\SystemClock;

// Dependencies (usually injected; $pdo and $psrLogger are host-provided, and the logger is optional)
$writer = new DeliveryOperationsLoggerMysqlRepository($pdo);
$clock = new SystemClock(new \DateTimeZone('UTC'));
$recorder = new DeliveryOperationsRecorder($writer, $clock, $psrLogger);

// Record Event
$recorder->record(
    channel: DeliveryChannelEnum::EMAIL,
    operationType: DeliveryOperationTypeEnum::NOTIFICATION,
    status: DeliveryStatusEnum::SENT,
    attemptNo: 1,
    actorType: 'SYSTEM',
    targetType: 'user',
    targetId: 456,
    provider: 'sendgrid',
    providerMessageId: 'msg_12345',
    metadata: ['template' => 'welcome_email']
);
```

### Constraints & Guards

- **Timezone**: Dates are strictly enforced as UTC.
- **Fail-Open**: Recording-flow exceptions are swallowed at the Recorder boundary. An optional
  PSR-3 fallback logger may receive a diagnostic; omitting it is valid.
- **Metadata**: MUST be an array or null. The Recorder structurally sanitizes nested sensitive
  associative keys before size/encoding handling and the writer boundary; maximum size is 64KB
  (JSON encoded). Arbitrary free-text secret detection is not provided.
- **String Constraints**: Strings are truncated to safe limits.

### Admin Query
Host applications may build administration or deep investigation screens using `DeliveryOperationsAdminQueryInterface::paginate()`.
This API natively calculates count, limits, offsets, and metadata mappings internally using `maatify/persistence`.
EventLogging owns domain filters, trusted SQL, selected columns, parameter construction, mapper behavior, and exception translation.
`maatify/persistence` owns page normalization, per-page clamping, offset calculation, ordering mechanics, count execution, `LIMIT`, `OFFSET`, and pagination metadata.
To support deep investigation, Admin Query Request DTO allows querying on unindexed columns like provider or error_message_like.
