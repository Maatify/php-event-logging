<?php

declare(strict_types=1);

require_once __DIR__ . '/00-bootstrap.php';
example_requires_pdo($pdo);

use Maatify\EventLogging\AuditTrail\DTO\AuditTrailAdminQueryRequestDTO;
use Maatify\EventLogging\AuditTrail\Infrastructure\Mysql\AuditTrailAdminQueryMysqlRepository;

/**
 * 09 - AuditTrail Admin Query
 *
 * Show the package-owned Admin Query offset-pagination API for an admin UI.
 * This is intentionally distinct from the protected primitive cursor API in example 11.
 */

// We assume $pdo is available from 00-bootstrap.php.
// @var \PDO $pdo

$repository = new AuditTrailAdminQueryMysqlRepository($pdo);
$request = new AuditTrailAdminQueryRequestDTO(
    actorType: 'user',
    actorId: 42,
    page: 1,
    perPage: 20,
    sortBy: 'occurred_at',
    sortDirection: 'DESC'
);

// Note: If you run this without a real database, it will throw an exception.
echo "Attempting to query audit trail...\n";
try {
    $page = $repository->paginate($request);
    echo sprintf(
        "Found %d page items (page %d/%d, per-page %d; %d filtered of %d total; next page: %s).\n",
        count($page->items),
        $page->page,
        $page->totalPages,
        $page->perPage,
        $page->filtered,
        $page->total,
        $page->hasNext ? 'yes' : 'no'
    );
    foreach ($page->items as $result) {
        echo "- Event: " . $result->eventKey . " at " . $result->occurredAt->format(\DateTimeInterface::ATOM) . "\n";
    }
} catch (\Throwable $e) {
    echo "Query failed (expected if DB is unreachable): " . $e->getMessage() . "\n";
}
