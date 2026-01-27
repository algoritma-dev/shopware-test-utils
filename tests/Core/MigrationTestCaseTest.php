<?php

namespace Algoritma\ShopwareTestUtils\Tests\Core;

use Algoritma\ShopwareTestUtils\Core\MigrationTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Framework\Migration\MigrationStep;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[CoversClass(MigrationTestCase::class)]
class MigrationTestCaseTest extends MigrationTestCase
{
    private Connection&MockObject $connection;

    private static ContainerInterface&MockObject $container;

    /**
     * @phpstan-ignore-next-line
     */
    protected function setUp(): void
    {
        // Bypass parent::setUp() to avoid Kernel boot
        // But we need to initialize what parent::setUp does if it's relevant.
        // MigrationTestCase::setUp calls snapshotDatabase() which uses getConnection().

        $this->connection = $this->createMock(Connection::class);
        self::$container = $this->createMock(ContainerInterface::class);

        self::$container->method('get')
            ->willReturnMap([
                [Connection::class, 1, $this->connection],
            ]);

        // We mock the snapshot logic by overriding setUp or just mocking the connection calls expected during snapshot.
        // Since we are testing the class itself, we should let it run its logic but with mocked dependencies.

        // Mocking snapshotDatabase calls
        $this->connection->method('fetchFirstColumn')
            ->willReturn([]); // Return empty tables list to skip snapshotting loop

        // Now we can call parent::setUp() safely?
        // No, parent::setUp() calls KernelTestBehaviour::setUp() which boots kernel.
        // We must NOT call parent::setUp() if we want to avoid kernel boot.
        // But MigrationTestCase::setUp() contains the logic we want to test (snapshotting).

        // Solution: We override setUp() in this test class to NOT call parent::setUp(),
        // but we manually invoke the logic we want to test or we structure the test so we call the methods directly.

        // However, the user wants to test MigrationTestCase.
        // If we don't call parent::setUp(), we skip the snapshot logic.
        // Let's just test the methods individually without the full lifecycle if possible,
        // or mock the parts that require kernel.
    }

    public function testAssertTableExists(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with(['test_table'])
            ->willReturn(true);

        $this->connection->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        $this->assertTableExists('test_table');
    }

    public function testAssertTableNotExists(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with(['test_table'])
            ->willReturn(false);

        $this->connection->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        $this->assertTableNotExists('test_table');
    }

    public function testAssertColumnExists(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
            ->method('listTableColumns')
            ->with('test_table')
            ->willReturn(['col_name' => 'dummy']);

        $this->connection->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        $this->assertColumnExists('test_table', 'col_name');
    }

    public function testExecuteMigration(): void
    {
        $migration = new class() extends MigrationStep {
            public function getCreationTimestamp(): int
            {
                return 123456;
            }

            public function update(Connection $connection): void
            {
                $connection->executeStatement('UPDATE_SQL');
            }

            public function updateDestructive(Connection $connection): void {}
        };

        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with('UPDATE_SQL');

        $this->executeMigration($migration::class);
    }

    // Override getContainer to return our mock
    protected static function getContainer(): ContainerInterface
    {
        return self::$container;
    }

    // Override getConnection to return our mock
    protected function getConnection(): Connection
    {
        return $this->connection;
    }
}
