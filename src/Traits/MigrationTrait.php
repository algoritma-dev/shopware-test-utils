<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Assert;

trait MigrationTrait
{
    /**
     * Asserts that a migration is idempotent (can be run multiple times without errors).
     */
    protected function assertMigrationIsIdempotent(string $migrationClass): void
    {
        // Run migration first time
        $this->executeMigration($migrationClass);

        // Run migration second time - should not fail
        try {
            $this->executeMigration($migrationClass);
            Assert::assertTrue(true, 'Migration is idempotent');
        } catch (\Throwable $e) {
            Assert::fail(sprintf(
                'Migration "%s" is not idempotent. Failed on second execution: %s',
                $migrationClass,
                $e->getMessage()
            ));
        }
    }

    /**
     * Asserts that schema changed after migration.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    protected function assertSchemaChanged(array $before, array $after): void
    {
        Assert::assertNotEquals(
            $before,
            $after,
            'Schema did not change after migration'
        );
    }

    /**
     * Asserts that schema did not change after migration.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    protected function assertSchemaUnchanged(array $before, array $after): void
    {
        Assert::assertEquals(
            $before,
            $after,
            'Schema changed but should not have'
        );
    }

    /**
     * Asserts that migration completes within a specified time.
     */
    protected function assertMigrationCompletesWithin(string $migrationClass, int $seconds): void
    {
        $start = microtime(true);

        $this->executeMigration($migrationClass);

        $duration = microtime(true) - $start;

        Assert::assertLessThanOrEqual(
            $seconds,
            $duration,
            sprintf('Migration "%s" took %.2f seconds, expected at most %d seconds', $migrationClass, $duration, $seconds)
        );
    }

    /**
     * Tests migration rollback (if supported).
     * Note: Shopware migrations typically don't support rollback, but this is here for completeness.
     */
    protected function testMigrationRollback(string $migrationClass, callable $rollbackLogic): void
    {
        // Take schema snapshot before migration
        $before = $this->getSchemaSnapshot();

        // Run migration
        $this->executeMigration($migrationClass);

        // Execute custom rollback logic
        $rollbackLogic($this->getConnection());

        // Take schema snapshot after rollback
        $after = $this->getSchemaSnapshot();

        // Assert schema is back to original state
        Assert::assertEquals(
            $before,
            $after,
            'Schema was not properly rolled back'
        );
    }

    /**
     * Asserts that a migration adds specific tables.
     */
    protected function assertMigrationAddsTable(string $migrationClass, string $tableName): void
    {
        $this->assertTableNotExists($tableName);
        $this->executeMigration($migrationClass);
        $this->assertTableExists($tableName);
    }

    /**
     * Asserts that a migration adds a specific column.
     */
    protected function assertMigrationAddsColumn(string $migrationClass, string $table, string $column): void
    {
        $this->executeMigration($migrationClass);
        $this->assertColumnExists($table, $column);
    }

    /**
     * Asserts that a migration removes a column.
     */
    protected function assertMigrationRemovesColumn(string $migrationClass, string $table, string $column): void
    {
        $this->assertColumnExists($table, $column);
        $this->executeMigrationDestructive($migrationClass);

        // Check if column was removed
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tableSchema = $this->introspectTable($schemaManager, $table);

        Assert::assertFalse(
            $tableSchema->hasColumn($column),
            sprintf('Column "%s" still exists in table "%s" after destructive migration', $column, $table)
        );
    }

    /**
     * Measures migration performance.
     *
     * @return array<string, mixed>
     */
    protected function measureMigrationPerformance(string $migrationClass): array
    {
        $start = microtime(true);
        $memoryBefore = memory_get_usage(true);

        $this->executeMigration($migrationClass);

        $memoryAfter = memory_get_usage(true);
        $duration = microtime(true) - $start;

        return [
            'duration' => $duration,
            'memory_used' => $memoryAfter - $memoryBefore,
            'memory_peak' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Tests data integrity after migration from old to new schema.
     *
     * @param string $oldTable The source table
     * @param string $newTable The destination table
     * @param callable $mappingCallback Function that maps old row to expected new row: fn($oldRow) => $newRow
     */
    protected function assertMigrationDataIntegrity(string $oldTable, string $newTable, callable $mappingCallback): void
    {
        $oldRows = $this->getConnection()->fetchAllAssociative("SELECT * FROM `{$oldTable}`");

        foreach ($oldRows as $oldRow) {
            $expectedNewRow = $mappingCallback($oldRow);
            $where = [];
            $params = [];

            foreach ($expectedNewRow as $column => $value) {
                $where[] = "`{$column}` = ?";
                $params[] = $value;
            }

            $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', $newTable, implode(' AND ', $where));
            $count = (int) $this->getConnection()->fetchOne($sql, $params);

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
    protected function testChunkedMigration(callable $migrationCallback, int $chunkSize, int $totalRows): void
    {
        Assert::assertGreaterThan(0, $chunkSize, 'Chunk size must be greater than 0');
        Assert::assertGreaterThanOrEqual(0, $totalRows, 'Total rows must be non-negative');

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
     */
    protected function assertDataTransformation(string $table, callable $validationCallback): void
    {
        $rows = $this->getConnection()->fetchAllAssociative("SELECT * FROM `{$table}`");
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
    protected function assertMigrationRowCountsMatch(string $sourceTable, string $destinationTable): void
    {
        $sourceCount = (int) $this->getConnection()->fetchOne("SELECT COUNT(*) FROM `{$sourceTable}`");
        $destCount = (int) $this->getConnection()->fetchOne("SELECT COUNT(*) FROM `{$destinationTable}`");

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
    protected function assertMigrationNoDataLoss(string $oldTable, string $newTable, string $uniqueColumn): void
    {
        $oldValues = $this->getConnection()->fetchFirstColumn("SELECT `{$uniqueColumn}` FROM `{$oldTable}`");
        $newValues = $this->getConnection()->fetchFirstColumn("SELECT `{$uniqueColumn}` FROM `{$newTable}`");

        $missing = array_diff($oldValues, $newValues);

        Assert::assertEmpty(
            $missing,
            sprintf(
                'Data loss detected: %d values from column "%s" in table "%s" are missing in table "%s": %s',
                count($missing),
                $uniqueColumn,
                $oldTable,
                $newTable,
                json_encode(array_slice($missing, 0, 10))
            )
        );
    }

    /**
     * Tests migration with data type conversions.
     */
    protected function assertMigrationColumnType(string $table, string $column, string $expectedType): void
    {
        $schemaManager = $this->getConnection()->createSchemaManager();
        $tableSchema = $this->introspectTable($schemaManager, $table);

        Assert::assertTrue($tableSchema->hasColumn($column), sprintf('Column "%s" does not exist in table "%s"', $column, $table));

        $type = $tableSchema->getColumn($column)->getType();
        $actualType = method_exists($type, 'getName')
            ? $type->getName()
            : Type::lookupName($type);

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
    protected function assertMigrationRelationalIntegrity(string $parentTable, string $childTable, string $foreignKeyColumn): void
    {
        $orphanedRows = $this->getConnection()->fetchAllAssociative(<<<EOD
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
     * Benchmarks a migration.
     *
     * @return array<string, mixed>
     */
    protected function benchmarkMigration(callable $migrationCallback, int $expectedRowCount): array
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

    /**
     * Tests migration with large dataset.
     */
    protected function testMigrationWithLargeDataset(string $migrationClass, string $table, int $rowCount): void
    {
        // Generate and insert test data
        $rows = [];
        for ($i = 0; $i < $rowCount; ++$i) {
            $rows[] = $this->generateTestRow($i);
        }

        $this->seedTable($table, $rows);

        // Run migration
        $performance = $this->measureMigrationPerformance($migrationClass);

        // Log performance metrics
        echo sprintf(
            "\nMigration performance with %d rows:\n  Duration: %.2f seconds\n  Memory: %.2f MB\n",
            $rowCount,
            $performance['duration'],
            $performance['memory_used'] / 1024 / 1024
        );
    }

    /**
     * Override this method to generate test rows specific to your migration.
     */
    /**
     * @return array<string, mixed>
     */
    protected function generateTestRow(int $index): array
    {
        return [
            'id' => bin2hex(random_bytes(16)),
            'data' => "test_data_{$index}",
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Abstract methods that need to be implemented by the test case.
     */
    abstract protected function executeMigration(string $migrationClass): void;

    abstract protected function executeMigrationDestructive(string $migrationClass): void;

    /**
     * @return array<string, mixed>
     */
    abstract protected function getSchemaSnapshot(): array;

    abstract protected function assertTableExists(string $table): void;

    abstract protected function assertTableNotExists(string $table): void;

    abstract protected function assertColumnExists(string $table, string $column): void;

    abstract protected function getConnection(): Connection;

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    abstract protected function seedTable(string $table, array $rows): void;
}
