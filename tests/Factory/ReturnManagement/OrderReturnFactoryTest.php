<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\ReturnManagement;

use Algoritma\ShopwareTestUtils\Factory\ReturnManagement\OrderReturnFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\ReturnManagement\Entity\OrderReturn\OrderReturnEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderReturnFactoryTest extends TestCase
{
    public function testCreateOrderReturn(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $return = new OrderReturnEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($return);

        $factory = new OrderReturnFactory($container);
        $result = $factory->create(Context::createDefaultContext());

        $this->assertInstanceOf(OrderReturnEntity::class, $result);
    }

    public function testWithOrder(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new OrderReturnFactory($container);

        $factory->withOrder('order-id');

        $this->assertInstanceOf(OrderReturnFactory::class, $factory);
    }
}
