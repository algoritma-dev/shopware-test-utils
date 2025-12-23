<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Assert;

trait MigrationHelpers
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
        $columns = $schemaManager->listTableColumns($table);

        Assert::assertArrayNotHasKey(
            $column,
            $columns,
            sprintf('Column "%s" still exists in table "%s" after destructive migration', $column, $table)
        );
    }

    /**
     * Measures migration performance.
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

    abstract protected function getSchemaSnapshot(): array;

    abstract protected function assertTableExists(string $table): void;

    abstract protected function assertTableNotExists(string $table): void;

    abstract protected function assertColumnExists(string $table, string $column): void;

    abstract protected function getConnection(): Connection;

    abstract protected function seedTable(string $table, array $rows): void;
}
