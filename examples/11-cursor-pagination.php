<?php

declare(strict_types=1);

require_once __DIR__ . '/00-bootstrap.php';
example_requires_pdo($pdo);

use Maatify\EventLogging\AuditTrail\Infrastructure\Mysql\AuditTrailQueryMysqlRepository;
use Maatify\EventLogging\AuditTrail\DTO\AuditTrailQueryDTO;

/**
 * 11 - Protected Primitive Cursor Pagination
 *
 * Demonstrate the protected primitive cursor API for compatibility reads.
 * Examples 09 and 10 use the separate Admin Query offset-pagination API.
 */

// We assume $pdo is available from 00-bootstrap.php.
// @var \PDO $pdo

$repository = new AuditTrailQueryMysqlRepository($pdo);
$queryDTO = new AuditTrailQueryDTO(
    cursorOccurredAt: new \DateTimeImmutable(),
    cursorId: 100,
    limit: 10
);

echo "Attempting to query audit trail with cursor pagination...\n";
try {
    $results = $repository->find($queryDTO);
    echo "Fetched page.\n";
} catch (\Throwable $e) {
    echo "Query failed (expected if DB is unreachable): " . $e->getMessage() . "\n";
}
