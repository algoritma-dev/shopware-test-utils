<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\MigrationDataTester;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

class MigrationDataTesterTest extends TestCase
{
    public function testDataIntegrity(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([['id' => 1, 'name' => 'test']]);
        $connection->method('fetchOne')->willReturn(1);

        $tester = new MigrationDataTester($connection);
        $tester->testDataIntegrity('old_table', 'new_table', fn ($row) => $row);

        $this->assertTrue(true); // Assertions are inside the method
    }

    public function testChunkedMigration(): void
    {
        $connection = $this->createStub(Connection::class);
        $tester = new MigrationDataTester($connection);

        $processed = 0;
        $callback = function ($offset, $limit) use (&$processed): void {
            $processed += $limit;
        };

        $tester->testChunkedMigration($callback, 10, 20);
        $this->assertEquals(20, $processed);
    }

    public function testVerifyDataTransformation(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([['id' => 1]]);

        $tester = new MigrationDataTester($connection);
        $tester->verifyDataTransformation('table', fn ($row): true => true);

        $this->assertTrue(true);
    }

    public function testAssertRowCountsMatch(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturn(10);

        $tester = new MigrationDataTester($connection);
        $tester->assertRowCountsMatch('table1', 'table2');

        $this->assertTrue(true);
    }

    public function testAssertNoDataLoss(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchFirstColumn')->willReturn([1, 2, 3]);

        $tester = new MigrationDataTester($connection);
        $tester->assertNoDataLoss('old', 'new', 'id');

        $this->assertTrue(true);
    }

    public function testDataTypeConversion(): void
    {
        $connection = $this->createStub(Connection::class);
        $schemaManager = $this->createStub(AbstractSchemaManager::class);
        $column = $this->createStub(Column::class);

        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $schemaManager->method('listTableColumns')->willReturn(['col' => $column]);
        $column->method('getType')->willReturn(Type::getType('string'));

        $tester = new MigrationDataTester($connection);
        $tester->testDataTypeConversion('table', 'col', 'string');

        $this->assertTrue(true);
    }

    public function testValidateRelationalIntegrity(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([]);

        $tester = new MigrationDataTester($connection);
        $tester->validateRelationalIntegrity('parent', 'child', 'fk');

        $this->assertTrue(true);
    }

    public function testBenchmarkMigration(): void
    {
        $connection = $this->createStub(Connection::class);
        $tester = new MigrationDataTester($connection);

        $result = $tester->benchmarkMigration(fn (): null => null, 100);

        $this->assertArrayHasKey('duration_seconds', $result);
        $this->assertEquals(100, $result['processed_rows']);
    }
}
