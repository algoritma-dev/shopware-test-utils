<?php

namespace Algoritma\ShopwareTestUtils\Core;

use Algoritma\ShopwareTestUtils\Traits\DatabaseHelpers;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use PHPUnit\Framework\Assert;
use Shopware\Core\Framework\Migration\MigrationStep;

abstract class MigrationTestCase extends AbstractIntegrationTestCase
{
    use DatabaseHelpers;

    /**
     * Executes a migration.
     */
    protected function executeMigration(string $migrationClass): void
    {
        /** @var MigrationStep $migration */
        $migration = new $migrationClass();
        $connection = $this->getConnection();

        $migration->update($connection);
    }

    /**
     * Executes the destructive part of a migration.
     */
    protected function executeMigrationDestructive(string $migrationClass): void
    {
        /** @var MigrationStep $migration */
        $migration = new $migrationClass();
        $connection = $this->getConnection();

        $migration->updateDestructive($connection);
    }

    /**
     * Asserts that a table exists.
     */
    protected function assertTableExists(string $table): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();

        Assert::assertTrue(
            $schemaManager->tablesExist([$table]),
            sprintf('Table "%s" does not exist', $table)
        );
    }

    /**
     * Asserts that a table does not exist.
     */
    protected function assertTableNotExists(string $table): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();

        Assert::assertFalse(
            $schemaManager->tablesExist([$table]),
            sprintf('Table "%s" exists but should not', $table)
        );
    }

    /**
     * Asserts that a column exists in a table.
     */
    protected function assertColumnExists(string $table, string $column): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns($table);

        Assert::assertArrayHasKey(
            $column,
            $columns,
            sprintf('Column "%s" does not exist in table "%s"', $column, $table)
        );
    }

    /**
     * Asserts that a column has a specific type.
     */
    protected function assertColumnType(string $table, string $column, string $expectedType): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        /** @var Column[] $columns */
        $columns = $schemaManager->listTableColumns($table);

        Assert::assertArrayHasKey($column, $columns, sprintf('Column "%s" does not exist in table "%s"', $column, $table));

        $actualType = $columns[$column]->getType()->getName();
        Assert::assertEquals(
            $expectedType,
            $actualType,
            sprintf('Column "%s.%s" has type "%s", expected "%s"', $table, $column, $actualType, $expectedType)
        );
    }

    /**
     * Asserts that an index exists on a table.
     */
    protected function assertIndexExists(string $table, string $index): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $indexes = $schemaManager->listTableIndexes($table);

        Assert::assertArrayHasKey(
            $index,
            $indexes,
            sprintf('Index "%s" does not exist on table "%s"', $index, $table)
        );
    }

    /**
     * Asserts that a foreign key exists on a table.
     */
    protected function assertForeignKeyExists(string $table, string $foreignKey): void
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        /** @var ForeignKeyConstraint[] $foreignKeys */
        $foreignKeys = $schemaManager->listTableForeignKeys($table);

        $found = false;
        foreach ($foreignKeys as $fk) {
            if ($fk->getName() === $foreignKey) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue($found, sprintf('Foreign key "%s" does not exist on table "%s"', $foreignKey, $table));
    }

    /**
     * Asserts that a trigger exists.
     */
    protected function assertTriggerExists(string $trigger): void
    {
        $connection = $this->getConnection();
        $result = $connection->fetchOne("SHOW TRIGGERS LIKE '{$trigger}'");

        Assert::assertNotFalse($result, sprintf('Trigger "%s" does not exist', $trigger));
    }

    /**
     * Creates a legacy table for testing data migration.
     *
     * @param array<string, string> $columns
     */
    protected function createLegacyTable(string $table, array $columns): void
    {
        $connection = $this->getConnection();

        $columnDefinitions = [];
        foreach ($columns as $name => $definition) {
            $columnDefinitions[] = "`{$name}` {$definition}";
        }

        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS `%s` (%s)',
            $table,
            implode(', ', $columnDefinitions)
        );

        $connection->executeStatement($sql);
    }

    /**
     * Seeds legacy data for testing migration.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    protected function seedLegacyData(string $table, array $rows): void
    {
        $this->seedTable($table, $rows);
    }

    /**
     * Asserts that data was correctly migrated.
     *
     * @param array<int, array<string, mixed>> $expected
     */
    protected function assertDataMigrated(string $table, array $expected): void
    {
        $connection = $this->getConnection();

        foreach ($expected as $row) {
            $where = [];
            $params = [];

            foreach ($row as $column => $value) {
                $where[] = "`{$column}` = ?";
                $params[] = $value;
            }

            $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', $table, implode(' AND ', $where));
            $count = (int) $connection->fetchOne($sql, $params);

            Assert::assertGreaterThan(
                0,
                $count,
                sprintf('Expected row not found in table "%s": %s', $table, json_encode($row))
            );
        }
    }

    /**
     * Asserts the row count of a table.
     */
    protected function assertRowCount(string $table, int $expectedCount): void
    {
        $connection = $this->getConnection();
        $count = (int) $connection->fetchOne("SELECT COUNT(*) FROM `{$table}`");

        Assert::assertEquals(
            $expectedCount,
            $count,
            sprintf('Table "%s" has %d rows, expected %d', $table, $count, $expectedCount)
        );
    }

    /**
     * Gets the schema snapshot of a table.
     *
     * @return array<string, mixed>
     */
    protected function getTableSchema(string $table): array
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();

        return [
            'columns' => $schemaManager->listTableColumns($table),
            'indexes' => $schemaManager->listTableIndexes($table),
            'foreignKeys' => $schemaManager->listTableForeignKeys($table),
        ];
    }

    /**
     * Gets a full schema snapshot.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getSchemaSnapshot(): array
    {
        $connection = $this->getConnection();
        $tables = $connection->fetchFirstColumn('SHOW TABLES');

        $snapshot = [];
        foreach ($tables as $table) {
            if (str_starts_with((string) $table, 'temp_snapshot_')) {
                continue;
            }
            $snapshot[$table] = $this->getTableSchema($table);
        }

        return $snapshot;
    }

    /**
     * Drops a table if it exists (for cleanup).
     */
    protected function dropTableIfExists(string $table): void
    {
        $connection = $this->getConnection();
        $connection->executeStatement("DROP TABLE IF EXISTS `{$table}`");
    }
}
