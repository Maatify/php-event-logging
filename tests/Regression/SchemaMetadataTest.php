<?php

declare(strict_types=1);

namespace Maatify\EventLogging\Tests\Regression;

use PHPUnit\Framework\TestCase;

final class SchemaMetadataTest extends TestCase
{
    public function testEveryColumnInTheSixDomainSchemasHasMeaningfulSqlComment(): void
    {
        $schemaFiles = [
            __DIR__ . '/../../src/AuditTrail/Database/schema.maa_event_logging_audit_trail.sql',
            __DIR__ . '/../../src/AuthoritativeAudit/Database/schema.maa_event_logging_authoritative_audit.sql',
            __DIR__ . '/../../src/BehaviorTrace/Database/schema.maa_event_logging_behavior_trace.sql',
            __DIR__ . '/../../src/DeliveryOperations/Database/schema.maa_event_logging_delivery_operations.sql',
            __DIR__ . '/../../src/DiagnosticsTelemetry/Database/schema.maa_event_logging_diagnostics_telemetry.sql',
            __DIR__ . '/../../src/SecuritySignals/Database/schema.maa_event_logging_security_signals.sql',
        ];

        $this->assertCount(6, $schemaFiles);

        $columnCount = 0;
        foreach ($schemaFiles as $schemaFile) {
            $this->assertFileExists($schemaFile);

            $columns = $this->extractColumnDefinitions((string) file_get_contents($schemaFile));
            $this->assertNotEmpty($columns, "Schema file has no column definitions: {$schemaFile}");

            foreach ($columns as $column) {
                ++$columnCount;
                $location = $column['table'] . '.' . $column['column'] . " in {$schemaFile}";

                $this->assertNotNull($column['comment'], "Column is missing a SQL COMMENT: {$location}");

                $comment = trim((string) $column['comment']);
                $this->assertNotSame('', $comment, "Column has an empty SQL COMMENT: {$location}");
                $words = preg_split('/\s+/', $comment);
                $this->assertIsArray($words);
                $this->assertGreaterThanOrEqual(2, count($words), "Column COMMENT is not meaningful: {$location}");
                $this->assertDoesNotMatchRegularExpression(
                    '/^(?:todo|tbd|comment|n\/a|none)$/i',
                    $comment,
                    "Column COMMENT is a placeholder: {$location}"
                );
            }
        }

        $this->assertGreaterThan(0, $columnCount);
    }

    /**
     * @return list<array{table: string, column: string, comment: string|null}>
     */
    private function extractColumnDefinitions(string $sql): array
    {
        $sql = preg_replace('/--[^\r\n]*(?:\r\n|\r|\n|$)/', '', $sql) ?? $sql;
        $columns = [];
        $offset = 0;

        while (preg_match(
            '/CREATE\s+TABLE\s+`?([A-Za-z_][A-Za-z0-9_]*)`?\s*\(/i',
            $sql,
            $matches,
            PREG_OFFSET_CAPTURE,
            $offset
        ) === 1) {
            $table = (string) $matches[1][0];
            $matchText = (string) $matches[0][0];
            $matchOffset = (int) $matches[0][1];
            $openPosition = $matchOffset + strlen($matchText) - 1;
            $closePosition = $this->findClosingParenthesis($sql, $openPosition);
            $body = substr($sql, $openPosition + 1, $closePosition - $openPosition - 1);

            foreach ($this->splitDefinitions($body) as $definition) {
                $definition = trim($definition);
                if ($definition === '' || preg_match(
                    '/^(?:PRIMARY\s+KEY|UNIQUE\s+KEY|(?:(?:FULLTEXT|SPATIAL)\s+)?INDEX|KEY|CONSTRAINT|CHECK|FOREIGN\s+KEY)\b/i',
                    $definition
                ) === 1) {
                    continue;
                }

                if (preg_match('/^`?([A-Za-z_][A-Za-z0-9_]*)`?\s+/', $definition, $columnMatch) !== 1) {
                    continue;
                }

                $comment = null;
                if (preg_match('/\bCOMMENT\s+\'((?:\'\'|[^\'])*)\'\s*$/i', $definition, $commentMatch) === 1) {
                    $comment = str_replace("''", "'", (string) $commentMatch[1]);
                }

                $columns[] = [
                    'table' => $table,
                    'column' => (string) $columnMatch[1],
                    'comment' => $comment,
                ];
            }

            $offset = $closePosition + 1;
        }

        return $columns;
    }

    private function findClosingParenthesis(string $sql, int $openPosition): int
    {
        $depth = 0;
        $quote = null;
        $length = strlen($sql);

        for ($position = $openPosition; $position < $length; ++$position) {
            $character = $sql[$position];

            if ($quote !== null) {
                if ($character === '\\' && $position + 1 < $length) {
                    ++$position;
                    continue;
                }
                if ($character === $quote) {
                    if ($position + 1 < $length && $sql[$position + 1] === $quote) {
                        ++$position;
                        continue;
                    }
                    $quote = null;
                }
                continue;
            }

            if ($character === "'" || $character === '`') {
                $quote = $character;
            } elseif ($character === '(') {
                ++$depth;
            } elseif ($character === ')') {
                --$depth;
                if ($depth === 0) {
                    return $position;
                }
            }
        }

        throw new \RuntimeException('Unbalanced CREATE TABLE parentheses.');
    }

    /** @return list<string> */
    private function splitDefinitions(string $body): array
    {
        $definitions = [];
        $start = 0;
        $depth = 0;
        $quote = null;
        $length = strlen($body);

        for ($position = 0; $position < $length; ++$position) {
            $character = $body[$position];

            if ($quote !== null) {
                if ($character === '\\' && $position + 1 < $length) {
                    ++$position;
                    continue;
                }
                if ($character === $quote) {
                    if ($position + 1 < $length && $body[$position + 1] === $quote) {
                        ++$position;
                        continue;
                    }
                    $quote = null;
                }
                continue;
            }

            if ($character === "'" || $character === '`') {
                $quote = $character;
            } elseif ($character === '(') {
                ++$depth;
            } elseif ($character === ')') {
                --$depth;
            } elseif ($character === ',' && $depth === 0) {
                $definitions[] = substr($body, $start, $position - $start);
                $start = $position + 1;
            }
        }

        $tail = trim(substr($body, $start));
        if ($tail !== '') {
            $definitions[] = $tail;
        }

        return $definitions;
    }
}
