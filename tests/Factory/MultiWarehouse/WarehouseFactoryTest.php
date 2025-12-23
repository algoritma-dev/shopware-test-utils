<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\MultiWarehouse;

use Algoritma\ShopwareTestUtils\Factory\MultiWarehouse\WarehouseFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\MultiWarehouse\Entity\Warehouse\WarehouseEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WarehouseFactoryTest extends TestCase
{
    public function testCreateWarehouse(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $warehouse = new WarehouseEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($warehouse);

        $factory = new WarehouseFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(WarehouseEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new WarehouseFactory($container);

        $factory->withName('Test Warehouse');

        $this->assertInstanceOf(WarehouseFactory::class, $factory);
    }
}
