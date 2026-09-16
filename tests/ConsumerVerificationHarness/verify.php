<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Maatify\EventLogging\DeliveryOperations\Command\RecordDeliveryOperationCommand;
use Maatify\EventLogging\DeliveryOperations\DTO\DeliveryOperationsQueryDTO;
use Maatify\EventLogging\DeliveryOperations\DTO\DeliveryOperationsViewDTO;
use Maatify\EventLogging\DeliveryOperations\Enum\DeliveryChannelEnum;
use Maatify\EventLogging\DeliveryOperations\Enum\DeliveryOperationTypeEnum;
use Maatify\EventLogging\DeliveryOperations\Enum\DeliveryStatusEnum;
use Maatify\EventLogging\DeliveryOperations\Infrastructure\Mysql\DeliveryOperationsQueryMysqlRepository;
use Maatify\EventLogging\Factory\DeliveryOperationsFactory;
use Maatify\SharedCommon\Infrastructure\SystemClock;

function requiredEnvironment(string $name): string
{
    $value = getenv($name);
    if (!is_string($value) || $value === '') {
        throw new RuntimeException("Missing required consumer harness environment variable: {$name}");
    }

    return $value;
}

$dsn = requiredEnvironment('EVENT_LOGGING_HARNESS_MYSQL_DSN');
$user = requiredEnvironment('EVENT_LOGGING_HARNESS_MYSQL_USER');
$password = requiredEnvironment('EVENT_LOGGING_HARNESS_MYSQL_PASSWORD');
$run = requiredEnvironment('EVENT_LOGGING_HARNESS_RUN');

$factoryReflection = new ReflectionClass(DeliveryOperationsFactory::class);
$factoryFile = $factoryReflection->getFileName();
if ($factoryFile === false || !str_contains(str_replace('\\', '/', $factoryFile), '/vendor/maatify/php-event-logging/')) {
    throw new RuntimeException('Consumer harness did not load the package through its installed production dependency.');
}

if (class_exists('Maatify\\EventLogging\\Tests\\Integration\\Support\\MysqlIntegrationTestCase')) {
    throw new RuntimeException('Consumer harness must not load the package test namespace.');
}

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tableName = 'maa_event_logging_delivery_operations';
$schemaPath = __DIR__ . '/vendor/maatify/php-event-logging/src/DeliveryOperations/Database/schema.maa_event_logging_delivery_operations.sql';
$schema = file_get_contents($schemaPath);
if (!is_string($schema)) {
    throw new RuntimeException('Consumer harness could not load the installed package schema asset.');
}

try {
    $pdo->exec("DROP TABLE IF EXISTS {$tableName}");
    $pdo->exec($schema);

    $requestId = 'consumer-harness-' . $run . '-' . bin2hex(random_bytes(8));
    $recorder = DeliveryOperationsFactory::create($pdo, new SystemClock(new DateTimeZone('UTC')));
    $recorder->recordCommand(new RecordDeliveryOperationCommand(
        channel: DeliveryChannelEnum::EMAIL,
        operationType: DeliveryOperationTypeEnum::NOTIFICATION,
        status: DeliveryStatusEnum::QUEUED,
        actorType: 'SYSTEM',
        targetType: 'consumer',
        requestId: $requestId,
        provider: 'consumer-harness',
        metadata: [
            'consumer' => 'verification-harness',
            'run' => $run,
        ]
    ));

    $results = (new DeliveryOperationsQueryMysqlRepository($pdo))->find(
        new DeliveryOperationsQueryDTO(requestId: $requestId)
    );
    if (count($results) !== 1
        || !isset($results[0])
        || get_class($results[0]) !== DeliveryOperationsViewDTO::class
    ) {
        throw new RuntimeException('Consumer workflow did not persist exactly one observable DeliveryOperations result.');
    }

    $result = $results[0];
    $actualMetadata = $result->metadata;
    if (is_array($actualMetadata)) {
        ksort($actualMetadata);
    }
    $expectedMetadata = ['consumer' => 'verification-harness', 'run' => $run];
    ksort($expectedMetadata);
    $actual = [
        'channel' => $result->channel,
        'operationType' => $result->operationType,
        'status' => $result->status,
        'actorType' => $result->actorType,
        'targetType' => $result->targetType,
        'requestId' => $result->requestId,
        'provider' => $result->provider,
        'metadata' => $actualMetadata,
    ];
    $expected = [
        'channel' => DeliveryChannelEnum::EMAIL->value,
        'operationType' => DeliveryOperationTypeEnum::NOTIFICATION->value,
        'status' => DeliveryStatusEnum::QUEUED->value,
        'actorType' => 'SYSTEM',
        'targetType' => 'consumer',
        'requestId' => $requestId,
        'provider' => 'consumer-harness',
        'metadata' => $expectedMetadata,
    ];
    if ($actual !== $expected) {
        throw new RuntimeException(sprintf(
            'Consumer workflow returned an unexpected persisted public result. Expected %s, got %s.',
            var_export($expected, true),
            var_export($actual, true)
        ));
    }

    echo "Consumer Verification Harness passed for clean run {$run}: persisted {$result->eventId}.\n";
} finally {
    $pdo->exec("DROP TABLE IF EXISTS {$tableName}");
}
