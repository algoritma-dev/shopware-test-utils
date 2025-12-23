<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Assert;

class MigrationDataTester
{
    public function __construct(private readonly Connection $connection) {}

    /**
     * Tests data integrity after migration from old to new schema.
     *
     * @param string $oldTable The source table
     * @param string $newTable The destination table
     * @param callable $mappingCallback Function that maps old row to expected new row: fn($oldRow) => $newRow
     */
    public function testDataIntegrity(string $oldTable, string $newTable, callable $mappingCallback): void
    {
        // Get all rows from old table
        $oldRows = $this->connection->fetchAllAssociative("SELECT * FROM `{$oldTable}`");

        foreach ($oldRows as $oldRow) {
            // Get expected new row using mapping callback
            $expectedNewRow = $mappingCallback($oldRow);

            // Build WHERE clause to find the migrated row
            $where = [];
            $params = [];

            foreach ($expectedNewRow as $column => $value) {
                $where[] = "`{$column}` = ?";
                $params[] = $value;
            }

            $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', $newTable, implode(' AND ', $where));
            $count = (int) $this->connection->fetchOne($sql, $params);

            Assert::assertGreaterThan(
                0,
                $count,
                sprintf(
                    'Data migration failed: Expected row not found in table "%s": %s (mapped from old row: %s)',
                    $newTable,
                    json_encode($expectedNewRow),
                    json_encode($oldRow)
                )
            );
        }
    }

    /**
     * Tests chunked migration (useful for large datasets).
     *
     * @param callable $migrationCallback Function that performs the migration: fn($offset, $limit)
     * @param int $chunkSize Number of rows per chunk
     * @param int $totalRows Total number of rows to migrate
     */
    public function testChunkedMigration(callable $migrationCallback, int $chunkSize, int $totalRows): void
    {
        $processedRows = 0;
        $chunks = (int) ceil($totalRows / $chunkSize);

        for ($i = 0; $i < $chunks; ++$i) {
            $offset = $i * $chunkSize;
            $limit = min($chunkSize, $totalRows - $offset);

            try {
                $migrationCallback($offset, $limit);
                $processedRows += $limit;
            } catch (\Throwable $e) {
                Assert::fail(sprintf(
                    'Chunked migration failed at chunk %d (offset: %d, limit: %d): %s',
                    $i + 1,
                    $offset,
                    $limit,
                    $e->getMessage()
                ));
            }
        }

        Assert::assertEquals(
            $totalRows,
            $processedRows,
            sprintf('Expected to process %d rows, but processed %d', $totalRows, $processedRows)
        );
    }

    /**
     * Verifies that data transformation was applied correctly.
     *
     * @param string $table The table to check
     * @param callable $validationCallback Function that validates each row: fn($row) => bool
     */
    public function verifyDataTransformation(string $table, callable $validationCallback): void
    {
        $rows = $this->connection->fetchAllAssociative("SELECT * FROM `{$table}`");
        $invalidRows = [];

        foreach ($rows as $row) {
            if (! $validationCallback($row)) {
                $invalidRows[] = $row;
            }
        }

        Assert::assertEmpty(
            $invalidRows,
            sprintf(
                'Data transformation validation failed for %d rows in table "%s": %s',
                count($invalidRows),
                $table,
                json_encode($invalidRows)
            )
        );
    }

    /**
     * Compares row counts before and after migration.
     */
    public function assertRowCountsMatch(string $sourceTable, string $destinationTable): void
    {
        $sourceCount = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM `{$sourceTable}`");
        $destCount = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM `{$destinationTable}`");

        Assert::assertEquals(
            $sourceCount,
            $destCount,
            sprintf(
                'Row count mismatch: source table "%s" has %d rows, destination table "%s" has %d rows',
                $sourceTable,
                $sourceCount,
                $destinationTable,
                $destCount
            )
        );
    }

    /**
     * Tests that no data was lost during migration.
     */
    public function assertNoDataLoss(string $oldTable, string $newTable, string $uniqueColumn): void
    {
        $oldValues = $this->connection->fetchFirstColumn("SELECT `{$uniqueColumn}` FROM `{$oldTable}`");
        $newValues = $this->connection->fetchFirstColumn("SELECT `{$uniqueColumn}` FROM `{$newTable}`");

        $missing = array_diff($oldValues, $newValues);

        Assert::assertEmpty(
            $missing,
            sprintf(
                'Data loss detected: %d values from column "%s" in table "%s" are missing in table "%s": %s',
                count($missing),
                $uniqueColumn,
                $oldTable,
                $newTable,
                json_encode(array_slice($missing, 0, 10)) // Show first 10 missing values
            )
        );
    }

    /**
     * Tests migration with data type conversions.
     */
    public function testDataTypeConversion(string $table, string $column, string $expectedType): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns($table);

        Assert::assertArrayHasKey($column, $columns, sprintf('Column "%s" does not exist in table "%s"', $column, $table));

        $type = $columns[$column]->getType();

        // Extract type name from class name (e.g., StringType -> string)
        $className = (new \ReflectionClass($type))->getShortName();
        $actualType = strtolower(str_replace('Type', '', $className));

        Assert::assertEquals(
            $expectedType,
            $actualType,
            sprintf(
                'Data type conversion failed: column "%s.%s" has type "%s", expected "%s"',
                $table,
                $column,
                $actualType,
                $expectedType
            )
        );
    }

    /**
     * Validates data consistency between related tables.
     */
    public function validateRelationalIntegrity(string $parentTable, string $childTable, string $foreignKeyColumn): void
    {
        $orphanedRows = $this->connection->fetchAllAssociative(<<<EOD

                        SELECT c.*
                        FROM `{$childTable}` c
                        LEFT JOIN `{$parentTable}` p ON c.`{$foreignKeyColumn}` = p.id
                        WHERE p.id IS NULL AND c.`{$foreignKeyColumn}` IS NOT NULL
                    
            EOD);

        Assert::assertEmpty(
            $orphanedRows,
            sprintf(
                'Relational integrity violation: %d orphaned rows found in table "%s" (foreign key: %s)',
                count($orphanedRows),
                $childTable,
                $foreignKeyColumn
            )
        );
    }

    /**
     * Tests migration performance with progress tracking.
     */
    public function benchmarkMigration(callable $migrationCallback, int $expectedRowCount): array
    {
        $start = microtime(true);
        $memoryBefore = memory_get_usage(true);

        $migrationCallback();

        $duration = microtime(true) - $start;
        $memoryUsed = memory_get_usage(true) - $memoryBefore;
        $peakMemory = memory_get_peak_usage(true);

        $throughput = ($expectedRowCount > 0 && $duration > 0) ? $expectedRowCount / $duration : 0;

        return [
            'duration_seconds' => $duration,
            'memory_used_mb' => $memoryUsed / 1024 / 1024,
            'peak_memory_mb' => $peakMemory / 1024 / 1024,
            'throughput_rows_per_second' => $throughput,
            'processed_rows' => $expectedRowCount,
        ];
    }
}
