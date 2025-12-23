<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Doctrine\DBAL\Connection;

trait DatabaseHelpers
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
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
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
