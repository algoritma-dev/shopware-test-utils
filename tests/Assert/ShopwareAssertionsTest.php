<?php

namespace Algoritma\ShopwareTestUtils\Tests\Assert;

use Algoritma\ShopwareTestUtils\Assert\ShopwareAssertions;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShopwareAssertionsTest extends TestCase
{
    use ShopwareAssertions;
    use KernelTestBehaviour;

    private static MockObject $container;

    protected function setUp(): void
    {
        self::$container = $this->createMock(ContainerInterface::class);
    }

    public function testAssertEntityExists(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);

        self::$container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('getTotal')->willReturn(1);

        $this->assertEntityExists('product', 'id');
    }

    public function testAssertCustomerHasRole(): void
    {
        $customer = $this->createMock(CustomerEntity::class);
        $group = $this->createMock(CustomerGroupEntity::class);

        $customer->method('getGroup')->willReturn($group);
        $group->method('getName')->willReturn('admin');

        $this->assertCustomerHasRole($customer, 'admin');
    }

    public function testAssertCartContainsProduct(): void
    {
        $cart = $this->createMock(Cart::class);
        $lineItem = $this->createMock(LineItem::class);
        $collection = new LineItemCollection([$lineItem]);

        $cart->method('getLineItems')->willReturn($collection);
        $lineItem->method('getReferencedId')->willReturn('product-id');

        $this->assertCartContainsProduct($cart, 'product-id');
    }

    public function testAssertDatabaseHas(): void
    {
        $connection = $this->createMock(Connection::class);
        $qb = $this->createMock(QueryBuilder::class);
        $result = $this->createMock(Result::class);

        self::$container->method('get')->willReturn($connection);
        $connection->method('createQueryBuilder')->willReturn($qb);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('expr')->willReturn($this->createMock(ExpressionBuilder::class));
        $qb->method('executeQuery')->willReturn($result);
        $result->method('fetchOne')->willReturn(1);

        $this->assertDatabaseHas('table', ['col' => 'val']);
    }

    public function testAssertTableExists(): void
    {
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        self::$container->method('get')->willReturn($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $schemaManager->method('tablesExist')->willReturn(true);

        $this->assertTableExists('table');
    }

    public function testAssertColumnExists(): void
    {
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        self::$container->method('get')->willReturn($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $schemaManager->method('listTableColumns')->willReturn(['col' => new Column('col', Type::getType('string'))]);

        $this->assertColumnExists('table', 'col');
    }

    public function testAssertIndexExists(): void
    {
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        self::$container->method('get')->willReturn($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $schemaManager->method('listTableIndexes')->willReturn(['idx' => new Index('idx', ['col'])]);

        $this->assertIndexExists('table', 'idx');
    }

    public function testAssertForeignKeyExists(): void
    {
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $fk = $this->createMock(ForeignKeyConstraint::class);

        self::$container->method('get')->willReturn($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $schemaManager->method('listTableForeignKeys')->willReturn([$fk]);
        $fk->method('getName')->willReturn('fk_name');

        $this->assertForeignKeyExists('table', 'fk_name');
    }

    public function testAssertRowCount(): void
    {
        $connection = $this->createMock(Connection::class);
        self::$container->method('get')->willReturn($connection);
        $connection->method('fetchOne')->willReturn(5);

        $this->assertRowCount('table', 5);
    }

    public function testAssertEntityNotExists(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);

        self::$container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('getTotal')->willReturn(0);

        $this->assertEntityNotExists('product', 'id');
    }

    public function testAssertEntityCount(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);

        self::$container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('getTotal')->willReturn(10);

        $this->assertEntityCount('product', 10);
    }

    public function testAssertCartTotal(): void
    {
        $cart = $this->createMock(Cart::class);
        $price = $this->createMock(CartPrice::class);

        $cart->method('getPrice')->willReturn($price);
        $price->method('getTotalPrice')->willReturn(100.0);

        $this->assertCartTotal($cart, 100.0);
    }

    public function testAssertProductInStock(): void
    {
        $product = $this->createMock(ProductEntity::class);
        $product->method('getStock')->willReturn(10);

        $this->assertProductInStock($product, 5);
    }

    public function testAssertProductActive(): void
    {
        $product = $this->createMock(ProductEntity::class);
        $product->method('getActive')->willReturn(true);

        $this->assertProductActive($product);
    }

    public function testAssertCustomerLoggedIn(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $customer = $this->createMock(CustomerEntity::class);

        $context->method('getCustomer')->willReturn($customer);

        $this->assertCustomerLoggedIn($context);
    }

    protected static function getContainer(): ContainerInterface
    {
        return self::$container;
    }
}
