<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Traits\DatabaseTrait;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatabaseHelpersTest extends TestCase
{
    use DatabaseTrait;

    private MockObject $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
    }

    public function testSnapshotTable(): void
    {
        // Mock calls for getInsertableColumns
        $this->connection->expects($this->any())
            ->method('fetchOne')
            ->willReturn('test_db'); // For SELECT DATABASE()

        $this->connection->expects($this->any())
            ->method('fetchFirstColumn')
            ->willReturn(['id', 'name']); // For columns

        // Expect CREATE TABLE for snapshot
        $this->connection->expects($this->any())
            ->method('executeStatement');

        $snapshotId = $this->snapshotTable('test_table');
        $this->assertNotEmpty($snapshotId);

        // Test Restore
        // Restore calls executeStatement for TRUNCATE, INSERT, DROP
        $this->restoreTableSnapshot($snapshotId);
    }

    public function testSnapshotSpecificTables(): void
    {
        $this->connection->expects($this->any())
            ->method('fetchOne')
            ->willReturn('test_db');

        $this->connection->expects($this->any())
            ->method('fetchFirstColumn')
            ->willReturn(['id', 'name']);

        $snapshotId = $this->snapshotSpecificTables(['table1', 'table2']);
        $this->assertNotEmpty($snapshotId);

        $this->restoreDatabaseSnapshot($snapshotId);
    }

    public function testTruncateTable(): void
    {
        $matcher = $this->exactly(3);
        $this->connection->expects($matcher)
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql) use ($matcher): int {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('SET FOREIGN_KEY_CHECKS = 0', $sql),
                    2 => $this->assertEquals('TRUNCATE TABLE `test_table`', $sql),
                    3 => $this->assertEquals('SET FOREIGN_KEY_CHECKS = 1', $sql),
                    default => null,
                };

                return 0;
            });

        $this->truncateTable('test_table');
    }

    public function testSeedTable(): void
    {
        $matcher = $this->exactly(2);
        $this->connection->expects($matcher)
            ->method('insert')
            ->willReturnCallback(function (string $table, array $data) use ($matcher): int {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals(['col' => 'val1'], $data),
                    2 => $this->assertEquals(['col' => 'val2'], $data),
                    default => null,
                };
                $this->assertEquals('test_table', $table);

                return 0;
            });

        $this->seedTable('test_table', [
            ['col' => 'val1'],
            ['col' => 'val2'],
        ]);
    }

    protected function getConnection(): Connection
    {
        return $this->connection;
    }
}
