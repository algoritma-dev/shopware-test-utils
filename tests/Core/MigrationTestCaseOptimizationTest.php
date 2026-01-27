<?php

namespace Algoritma\ShopwareTestUtils\Tests\Core;

use Algoritma\ShopwareTestUtils\Core\MigrationTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[CoversClass(MigrationTestCase::class)]
class MigrationTestCaseOptimizationTest extends MigrationTestCase
{
    private Connection&MockObject $connection;

    private static ContainerInterface&MockObject $container;

    /**
     * @phpstan-ignore-next-line
     */
    protected function setUp(): void
    {
        // Do NOT call parent::setUp() to avoid Kernel boot

        $this->connection = $this->createMock(Connection::class);
        self::$container = $this->createMock(ContainerInterface::class);

        self::$container->method('get')
            ->willReturnMap([
                [Connection::class, 1, $this->connection],
            ]);
    }

    public function testSnapshotOptimizationRespectsConfig(): void
    {
        // We need to simulate the logic that happens in setUp() but without calling parent::setUp()
        // because parent::setUp() boots the kernel.
        // We can manually invoke the logic if we extract it or just test the property setting if possible.

        // Since we can't easily test the setUp logic without running it, and running it requires Kernel,
        // we will simulate the behavior by manually calling the snapshot methods and verifying calls.

        // Set the property
        $this->tablesToSnapshot = ['table1', 'table2'];

        // Mock expectations for snapshotSpecificTables
        $this->connection->expects($this->any())
            ->method('fetchOne')
            ->willReturn('test_db');

        $this->connection->expects($this->any())
            ->method('fetchFirstColumn')
            ->willReturn(['col1']);

        // Expect CREATE TABLE calls for the specific tables
        $this->connection->expects($this->exactly(2))
            ->method('executeStatement')
            ->with($this->stringContains('CREATE TABLE'));

        // Manually trigger the logic that would be in setUp
        $this->snapshotSpecificTables($this->tablesToSnapshot);
    }

    public function testTransactionRollbackLogic(): void
    {
        $this->useTransactionRollback = true;

        $this->connection->expects($this->once())
            ->method('beginTransaction');

        // Simulate setUp logic
        $this->beginTransaction();

        // Simulate tearDown logic
        $this->connection->expects($this->once())
            ->method('rollBack');

        $this->rollbackTransaction();
    }

    protected static function getContainer(): ContainerInterface
    {
        return self::$container;
    }

    protected function getConnection(): Connection
    {
        return $this->connection;
    }
}
