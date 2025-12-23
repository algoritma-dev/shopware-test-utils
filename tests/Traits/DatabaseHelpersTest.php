<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Traits\DatabaseHelpers;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatabaseHelpersTest extends TestCase
{
    use DatabaseHelpers;

    /**
     * @var Connection&MockObject
     */
    private MockObject $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
    }

    public function testTruncateTable(): void
    {
        $table = 'test_table';

        $matcher = $this->exactly(3);
        $this->connection->expects($matcher)
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql) use ($matcher, $table): int {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('SET FOREIGN_KEY_CHECKS = 0', $sql),
                    2 => $this->assertEquals("TRUNCATE TABLE `{$table}`", $sql),
                    3 => $this->assertEquals('SET FOREIGN_KEY_CHECKS = 1', $sql),
                    default => $this->fail('Unexpected invocation count: ' . $matcher->numberOfInvocations()),
                };

                return 0;
            });

        $this->truncateTable($table);
    }

    public function testSeedTable(): void
    {
        $table = 'test_table';
        $rows = [
            ['id' => 1, 'name' => 'Test 1'],
            ['id' => 2, 'name' => 'Test 2'],
        ];

        $matcher = $this->exactly(2);
        $this->connection->expects($matcher)
            ->method('insert')
            ->willReturnCallback(function (string $tableName, array $data) use ($matcher, $table, $rows): int {
                $this->assertEquals($table, $tableName);
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals($rows[0], $data),
                    2 => $this->assertEquals($rows[1], $data),
                    default => $this->fail('Unexpected invocation count: ' . $matcher->numberOfInvocations()),
                };

                return 0;
            });

        $this->seedTable($table, $rows);
    }

    public function testSnapshotAndRestoreTable(): void
    {
        $table = 'test_table';
        $columns = ['id', 'name'];

        // Mocking schema retrieval
        $this->connection->method('fetchOne')
            ->with('SELECT DATABASE()')
            ->willReturn('test_db');

        $this->connection->method('fetchFirstColumn')
            ->with($this->stringContains('SELECT COLUMN_NAME'))
            ->willReturn($columns);

        // We expect a sequence of calls for snapshotting and then restoring
        // Snapshot: CREATE temp AS SELECT ...
        // Restore: SET FK 0, TRUNCATE, INSERT INTO original, SET FK 1, DROP temp

        $matcher = $this->exactly(6);
        $this->connection->expects($matcher)
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql) use ($matcher, $table): int {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertTrue(
                        str_starts_with($sql, 'CREATE TABLE `temp_snapshot_')
                        && str_contains($sql, "AS SELECT `id`, `name` FROM `{$table}`")
                    ),
                    2 => $this->assertEquals('SET FOREIGN_KEY_CHECKS = 0', $sql),
                    3 => $this->assertEquals("TRUNCATE TABLE `{$table}`", $sql),
                    4 => $this->assertTrue(
                        str_contains($sql, "INSERT INTO `{$table}` (`id`, `name`) SELECT `id`, `name` FROM `temp_snapshot_")
                    ),
                    5 => $this->assertEquals('SET FOREIGN_KEY_CHECKS = 1', $sql),
                    6 => $this->assertTrue(str_starts_with($sql, 'DROP TABLE IF EXISTS `temp_snapshot_')),
                    default => $this->fail('Unexpected invocation count: ' . $matcher->numberOfInvocations()),
                };

                return 0;
            });

        $snapshotId = $this->snapshotTable($table);
        $this->restoreTableSnapshot($snapshotId);
    }

    public function testGeneratedColumnsAreExcludedFromSnapshot(): void
    {
        $table = 'table_with_generated';
        // Simulate that the database only returns non-generated columns
        $columns = ['id', 'price', 'quantity'];
        // 'total' (generated) is excluded from this list by the query in getInsertableColumns

        $this->connection->method('fetchOne')
            ->with('SELECT DATABASE()')
            ->willReturn('test_db');

        $this->connection->method('fetchFirstColumn')
            ->with($this->stringContains('SELECT COLUMN_NAME'))
            ->willReturn($columns);

        $this->connection->expects($this->atLeastOnce())
            ->method('executeStatement')
            ->with($this->callback(function ($sql): bool {
                if (str_starts_with($sql, 'CREATE TABLE')) {
                    // Verify that the generated SQL only contains the columns we returned
                    // and specifically does NOT contain a hypothetical generated column if we were to assume one
                    return str_contains($sql, '`id`, `price`, `quantity`')
                           && ! str_contains($sql, '`total`');
                }

                return true;
            }));

        $this->snapshotTable($table);
    }

    public function testSnapshotDatabase(): void
    {
        $tables = ['table1', 'table2'];
        $columns = ['id', 'data'];

        $this->connection->method('fetchFirstColumn')
            ->willReturnCallback(function (string $query, array $params = []) use ($tables, $columns): array {
                if ($query === 'SHOW TABLES') {
                    return $tables;
                }
                if (str_contains($query, 'SELECT COLUMN_NAME')) {
                    return $columns;
                }

                return [];
            });

        $this->connection->method('fetchOne')
            ->willReturn('test_db');

        // Expect creation of temp tables for each table (1 statement per table)
        $matcher = $this->exactly(2);
        $this->connection->expects($matcher)
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql) use ($matcher): int {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertStringStartsWith('CREATE TABLE `ts_', $sql),
                    2 => $this->assertStringStartsWith('CREATE TABLE `ts_', $sql),
                    default => $this->fail('Unexpected invocation count: ' . $matcher->numberOfInvocations()),
                };

                return 0;
            });

        $this->snapshotDatabase();
    }

    protected function getConnection(): Connection
    {
        return $this->connection;
    }
}
