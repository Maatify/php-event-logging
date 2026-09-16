# Manual Wiring

The `maatify/php-event-logging` library is completely container and framework agnostic. While you can use the provided factories, you might prefer or need to manually wire the dependencies yourself (e.g. to inject custom policies or when adhering to specific DI paradigms). This document outlines how to manually wire the components of a logging domain.

## Preserving Domain Isolation

A core architectural principle of this library is the strict isolation of domains. There are no generic "loggers", "recorders", "repositories", or "DTOs". When manually wiring, you must instantiate the specific classes for each domain you intend to use.

## No Internal Framework Bindings

The package contains no bindings or auto-discovery configuration for frameworks such as Laravel, Symfony, Slim, or specific containers like PHP-DI. The host application's container is entirely responsible for discovering, instantiating, and managing the lifecycle of these objects externally.

## Host-Managed Infrastructure Dependencies

When integrating this package, the host application is responsible for providing critical infrastructure instances:

### PDO Connection
The package does not own or manage the creation of `PDO` connections. It expects an already configured `PDO` instance to be injected into its factories or directly into its MySQL repositories.
To ensure compatibility with the provided schema, the host application must ensure the connection uses the `utf8mb4` character set (specifically `utf8mb4_unicode_ci` collation). Any specific PDO options or charset configurations should be handled at the application level before passing the connection to the package.

### Fallback Logging (PSR-3)
For domains that support fail-open behavior, the package relies on the generic `Psr\Log\LoggerInterface`. The package does not require any specific logging implementation (e.g., Monolog). Any PSR-3 compatible logger provided by the host application is sufficient.
Note that injecting a fallback logger does not change the fundamental failure semantics of the domains: `AuthoritativeAudit` remains strictly fail-closed (it does not accept a fallback logger), while other domains use the fallback logger only at the Recorder boundary to report recording-flow failures before failing open. The fallback logger is optional, and its absence is valid.

## Manually Constructing Domain Recorders and Repositories

Wiring a domain typically involves constructing an infrastructure repository (the writer), and then injecting that into the domain recorder along with a clock and an optional fallback logger.

### Example: Wiring the Audit Trail Domain

```php
use Maatify\EventLogging\AuditTrail\Infrastructure\Mysql\AuditTrailLoggerMysqlRepository;
use Maatify\EventLogging\AuditTrail\Recorder\AuditTrailRecorder;
use Maatify\SharedCommon\Infrastructure\SystemClock;

// 1. Host provides dependencies
$pdo = /* ... PDO instance ... */;
$clock = new SystemClock(new \DateTimeZone('UTC'));
$psrLogger = /* ... PSR-3 Logger Interface ... */;

// 2. Construct the specific repository/writer
$auditTrailRepository = new AuditTrailLoggerMysqlRepository($pdo);

// 3. Construct the recorder, injecting the repository, clock, and PSR logger
$auditTrailRecorder = new AuditTrailRecorder(
    logger: $auditTrailRepository,
    clock: $clock,
    fallbackLogger: $psrLogger, // Optional fail-open behavior
    policy: null
);
```

### Injecting Custom Policies

Some domains use policies to determine specific behavior, which you can customize when manually wiring. For instance, the `AuthoritativeAudit` domain uses a Policy to normalize actor types and validate payload safety. A custom implementation must preserve the complete recursive payload-safety contract; checking only one field such as `credit_card` is not sufficient.

```php
use Maatify\EventLogging\AuthoritativeAudit\Infrastructure\Mysql\AuthoritativeAuditOutboxWriterMysqlRepository;
use Maatify\EventLogging\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder;
use Maatify\EventLogging\AuthoritativeAudit\Contract\AuthoritativeAuditPolicyInterface;
use Maatify\EventLogging\AuthoritativeAudit\Enum\AuthoritativeAuditActorTypeInterface;
use Maatify\SharedCommon\Infrastructure\SystemClock;

// The host supplies both the PDO connection and the complete recursive payload validator.
function wireAuthoritativeAudit(\PDO $pdo, \Closure $payloadValidator): AuthoritativeAuditRecorder
{
    $clock = new SystemClock(new \DateTimeZone('UTC'));

    $customPolicy = new class($payloadValidator) implements AuthoritativeAuditPolicyInterface {
        public function __construct(
            private readonly \Closure $payloadValidator
        ) {
        }

        public function validatePayload(array $payload): bool
        {
            return ($this->payloadValidator)($payload);
        }

        public function normalizeActorType(AuthoritativeAuditActorTypeInterface|string $actorType): string
        {
            return is_string($actorType) ? $actorType : $actorType->value();
        }
    };

    $authoritativeAuditRepository = new AuthoritativeAuditOutboxWriterMysqlRepository($pdo);

    return new AuthoritativeAuditRecorder(
        writer: $authoritativeAuditRepository,
        clock: $clock,
        policy: $customPolicy
        // Note: No PSR logger is provided here because this domain is fail-closed.
    );
}
```

By manually wiring, your application retains complete control over the configuration and behavior of the logging domains while keeping the core package strictly focused on logging operations.
