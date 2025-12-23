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
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $return = new OrderReturnEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($return);

        $factory = new OrderReturnFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(OrderReturnEntity::class, $result);
    }

    public function testWithOrder(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new OrderReturnFactory($container);

        $factory->withOrder('order-id');

        $this->assertInstanceOf(OrderReturnFactory::class, $factory);
    }
}
