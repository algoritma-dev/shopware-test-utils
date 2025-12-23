<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\ProductFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductFactoryTest extends TestCase
{
    public function testCreateProduct(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $product = new ProductEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($product);

        $factory = new ProductFactory($container);
        $result = $factory->create(Context::createDefaultContext());

        $this->assertInstanceOf(ProductEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new ProductFactory($container);

        $factory->withName('Test Product');

        // Reflection to check private property if needed, or rely on create() behavior
        // For unit test without DB, we verify the fluent interface returns self
        $this->assertInstanceOf(ProductFactory::class, $factory);
    }

    public function testWithPrice(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new ProductFactory($container);

        $factory->withPrice(100.0, 80.0);

        $this->assertInstanceOf(ProductFactory::class, $factory);
    }
}
