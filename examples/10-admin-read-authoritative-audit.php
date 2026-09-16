<?php

declare(strict_types=1);

require_once __DIR__ . '/00-bootstrap.php';
example_requires_pdo($pdo);

use Maatify\EventLogging\AuthoritativeAudit\DTO\AuthoritativeAuditAdminQueryRequestDTO;
use Maatify\EventLogging\AuthoritativeAudit\Infrastructure\Mysql\AuthoritativeAuditAdminQueryMysqlRepository;

/**
 * 10 - AuthoritativeAudit Admin Query
 *
 * Show the package-owned Admin Query offset-pagination API for authoritative audit logs.
 * Reads come from the materialized log; this example never reads the outbox.
 * This is intentionally distinct from the protected primitive cursor API in example 11.
 */

// We assume $pdo is available from 00-bootstrap.php.
// @var \PDO $pdo

$repository = new AuthoritativeAuditAdminQueryMysqlRepository($pdo);
$request = new AuthoritativeAuditAdminQueryRequestDTO(
    actorType: 'admin',
    actorId: 1,
    action: 'role.assign',
    page: 1,
    perPage: 20,
    sortBy: 'occurred_at',
    sortDirection: 'DESC'
);

echo "Attempting to query authoritative audit...\n";
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
        echo "- Action: " . $result->action . " at " . $result->occurredAt->format(\DateTimeInterface::ATOM) . "\n";
    }
} catch (\Throwable $e) {
    echo "Query failed (expected if DB is unreachable): " . $e->getMessage() . "\n";
}
