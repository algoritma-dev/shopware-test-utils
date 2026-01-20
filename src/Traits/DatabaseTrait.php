<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Doctrine\DBAL\Connection;

trait DatabaseTrait
{
    private array $databaseSnapshots = [];

    /**
     * Truncates a table.
     */
    protected function truncateTable(string $table): void
    {
        $connection = $this->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement("TRUNCATE TABLE `{$table}`");
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Seeds a table with data.
     */
    protected function seedTable(string $table, array $rows): void
    {
        $connection = $this->getConnection();

        foreach ($rows as $row) {
            $connection->insert($table, $row);
        }
    }

    /**
     * Creates a snapshot of a table.
     */
    protected function snapshotTable(string $table): string
    {
        $connection = $this->getConnection();
        $snapshotId = uniqid('snapshot_', true);
        $tempTable = "temp_snapshot_{$snapshotId}";

        $columns = $this->getInsertableColumns($table);
        $columnList = implode('`, `', $columns);

        $connection->executeStatement("CREATE TABLE `{$tempTable}` AS SELECT `{$columnList}` FROM `{$table}`");

        $this->databaseSnapshots[$snapshotId] = [
            'table' => $table,
            'tempTable' => $tempTable,
            'columns' => $columns,
        ];

        return $snapshotId;
    }

    /**
     * Restores a table from a snapshot.
     */
    protected function restoreTableSnapshot(string $snapshotId): void
    {
        if (! isset($this->databaseSnapshots[$snapshotId])) {
            throw new \RuntimeException("Snapshot {$snapshotId} not found");
        }

        $connection = $this->getConnection();
        $snapshot = $this->databaseSnapshots[$snapshotId];

        $columns = $snapshot['columns'] ?? $this->getInsertableColumns($snapshot['table']);
        $columnList = implode('`, `', $columns);

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement("TRUNCATE TABLE `{$snapshot['table']}`");
        $connection->executeStatement("INSERT INTO `{$snapshot['table']}` (`{$columnList}`) SELECT `{$columnList}` FROM `{$snapshot['tempTable']}`");
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $connection->executeStatement("DROP TABLE IF EXISTS `{$snapshot['tempTable']}`");
        unset($this->databaseSnapshots[$snapshotId]);
    }

    /**
     * Drops a snapshot table.
     */
    protected function dropSnapshot(string $snapshotId): void
    {
        if (! isset($this->databaseSnapshots[$snapshotId])) {
            return;
        }

        $connection = $this->getConnection();
        $snapshot = $this->databaseSnapshots[$snapshotId];

        $connection->executeStatement("DROP TABLE IF EXISTS `{$snapshot['tempTable']}`");
        unset($this->databaseSnapshots[$snapshotId]);
    }

    /**
     * Creates a full database snapshot.
     */
    protected function snapshotDatabase(): string
    {
        $connection = $this->getConnection();
        // Use shorter ID to prevent table name overflow (max 64 chars)
        $snapshotId = uniqid('', true);

        $tables = $connection->fetchFirstColumn('SHOW TABLES');

        foreach ($tables as $table) {
            if (str_starts_with((string) $table, 'temp_snapshot_')) {
                continue;
            }
            if (str_starts_with((string) $table, 'ts_')) {
                continue;
            }
            $tempTable = "ts_{$snapshotId}_{$table}";

            // Handle extremely long table names by hashing if necessary
            if (strlen($tempTable) > 64) {
                $tempTable = "ts_{$snapshotId}_" . md5((string) $table);
            }

            $columns = $this->getInsertableColumns((string) $table);
            $columnList = implode('`, `', $columns);

            $connection->executeStatement("CREATE TABLE `{$tempTable}` AS SELECT `{$columnList}` FROM `{$table}`");

            $this->databaseSnapshots[$snapshotId][] = [
                'table' => $table,
                'tempTable' => $tempTable,
                'columns' => $columns,
            ];
        }

        return $snapshotId;
    }

    /**
     * Creates a snapshot of specific tables.
     *
     * @param array<string> $tables
     */
    protected function snapshotSpecificTables(array $tables): string
    {
        $connection = $this->getConnection();
        $snapshotId = uniqid('', true);

        foreach ($tables as $table) {
            $tempTable = "ts_{$snapshotId}_{$table}";

            // Handle extremely long table names by hashing if necessary
            if (strlen($tempTable) > 64) {
                $tempTable = "ts_{$snapshotId}_" . md5((string) $table);
            }

            $columns = $this->getInsertableColumns((string) $table);
            $columnList = implode('`, `', $columns);

            $connection->executeStatement("CREATE TABLE `{$tempTable}` AS SELECT `{$columnList}` FROM `{$table}`");

            $this->databaseSnapshots[$snapshotId][] = [
                'table' => $table,
                'tempTable' => $tempTable,
                'columns' => $columns,
            ];
        }

        return $snapshotId;
    }

    /**
     * Restores full database from snapshot.
     */
    protected function restoreDatabaseSnapshot(string $snapshotId): void
    {
        if (! isset($this->databaseSnapshots[$snapshotId])) {
            throw new \RuntimeException("Database snapshot {$snapshotId} not found");
        }

        $connection = $this->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($this->databaseSnapshots[$snapshotId] as $snapshot) {
            $columns = $snapshot['columns'] ?? $this->getInsertableColumns($snapshot['table']);
            $columnList = implode('`, `', $columns);

            $connection->executeStatement("TRUNCATE TABLE `{$snapshot['table']}`");
            $connection->executeStatement("INSERT INTO `{$snapshot['table']}` (`{$columnList}`) SELECT `{$columnList}` FROM `{$snapshot['tempTable']}`");
            $connection->executeStatement("DROP TABLE IF EXISTS `{$snapshot['tempTable']}`");
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        unset($this->databaseSnapshots[$snapshotId]);
    }

    /**
     * Executes a raw SQL query.
     */
    protected function queryRaw(string $sql, array $params = []): array
    {
        $connection = $this->getConnection();

        return $connection->fetchAllAssociative($sql, $params);
    }

    /**
     * Begins a manual transaction.
     */
    protected function beginTransaction(): void
    {
        $this->getConnection()->beginTransaction();
    }

    /**
     * Rolls back a manual transaction.
     */
    protected function rollbackTransaction(): void
    {
        $this->getConnection()->rollBack();
    }

    /**
     * Commits a manual transaction.
     */
    protected function commitTransaction(): void
    {
        $this->getConnection()->commit();
    }

    /**
     * Gets the Connection instance.
     */
    abstract protected function getConnection(): Connection;

    // --- Database Assertions ---

    /**
     * Assert that a table contains a row with the given conditions.
     *
     * @param array<string, mixed> $conditions
     */
    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        $connection = $this->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('COUNT(*)')
            ->from($table);

        foreach ($conditions as $column => $value) {
            $qb->andWhere($qb->expr()->eq($column, $qb->createNamedParameter($value)));
        }

        $count = (int) $qb->executeQuery()->fetchOne();
        static::assertGreaterThan(0, $count, sprintf('Failed asserting that table "%s" contains row with conditions: %s', $table, json_encode($conditions)));
    }

    /**
     * Assert that a table does not contain a row with the given conditions.
     *
     * @param array<string, mixed> $conditions
     */
    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        $connection = $this->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('COUNT(*)')
            ->from($table);

        foreach ($conditions as $column => $value) {
            $qb->andWhere($qb->expr()->eq($column, $qb->createNamedParameter($value)));
        }

        $count = (int) $qb->executeQuery()->fetchOne();
        static::assertEquals(0, $count, sprintf('Failed asserting that table "%s" does not contain row with conditions: %s', $table, json_encode($conditions)));
    }

    /**
     * Assert that a table exists.
     */
    protected function assertTableExists(string $table): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        static::assertTrue($schemaManager->tablesExist([$table]), sprintf('Table "%s" does not exist.', $table));
    }

    /**
     * Assert that a table does not exist.
     */
    protected function assertTableNotExists(string $table): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        static::assertFalse($schemaManager->tablesExist([$table]), sprintf('Table "%s" exists but should not.', $table));
    }

    /**
     * Assert that a column exists in a table.
     */
    protected function assertColumnExists(string $table, string $column): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns($table);
        static::assertArrayHasKey($column, $columns, sprintf('Column "%s" does not exist in table "%s".', $column, $table));
    }

    /**
     * Assert that a column has a specific type.
     */
    protected function assertColumnType(string $table, string $column, string $expectedType): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns($table);
        static::assertArrayHasKey($column, $columns, sprintf('Column "%s" does not exist in table "%s".', $column, $table));

        $actualType = $columns[$column]->getType()->getName();
        static::assertEquals($expectedType, $actualType, sprintf('Column "%s.%s" has type "%s", expected "%s".', $table, $column, $actualType, $expectedType));
    }

    /**
     * Assert that an index exists on a table.
     */
    protected function assertIndexExists(string $table, string $index): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $indexes = $schemaManager->listTableIndexes($table);
        static::assertArrayHasKey($index, $indexes, sprintf('Index "%s" does not exist on table "%s".', $index, $table));
    }

    /**
     * Assert that a foreign key exists on a table.
     */
    protected function assertForeignKeyExists(string $table, string $foreignKey): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $foreignKeys = $schemaManager->listTableForeignKeys($table);

        $found = false;
        foreach ($foreignKeys as $fk) {
            if ($fk->getName() === $foreignKey) {
                $found = true;
                break;
            }
        }

        static::assertTrue($found, sprintf('Foreign key "%s" does not exist on table "%s".', $foreignKey, $table));
    }

    /**
     * Assert that a table has an expected number of rows.
     */
    protected function assertRowCount(string $table, int $expectedCount): void
    {
        $connection = $this->getConnection();
        $count = (int) $connection->fetchOne("SELECT COUNT(*) FROM `{$table}`");
        static::assertEquals($expectedCount, $count, sprintf('Table "%s" has %d rows, expected %d.', $table, $count, $expectedCount));
    }

    /**
     * Gets columns that can be inserted into (excludes generated columns).
     */
    private function getInsertableColumns(string $table): array
    {
        $connection = $this->getConnection();
        $database = $connection->fetchOne('SELECT DATABASE()');

        $sql = <<<'EOD'

                        SELECT COLUMN_NAME
                        FROM information_schema.COLUMNS
                        WHERE TABLE_SCHEMA = :database
                        AND TABLE_NAME = :table
                        AND EXTRA NOT LIKE '%GENERATED%'

            EOD;

        return $connection->fetchFirstColumn($sql, ['database' => $database, 'table' => $table]);
    }
}
