<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\MultiWarehouse;

use Algoritma\ShopwareTestUtils\Factory\MultiWarehouse\WarehouseGroupFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\MultiWarehouse\Entity\WarehouseGroup\WarehouseGroupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WarehouseGroupFactoryTest extends TestCase
{
    public function testCreateWarehouseGroup(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $group = new WarehouseGroupEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($group);

        $factory = new WarehouseGroupFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(WarehouseGroupEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new WarehouseGroupFactory($container);

        $factory->withName('Test Group');

        $this->assertInstanceOf(WarehouseGroupFactory::class, $factory);
    }
}
