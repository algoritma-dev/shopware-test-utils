<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\OrderFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderFactoryTest extends TestCase
{
    public function testCreateOrder(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $idSearchResult = $this->createMock(IdSearchResult::class);
        $order = new OrderEntity();
        $connection = $this->createMock(Connection::class);

        $container->method('get')->willReturnMap([
            ['order.repository', 1, $repository],
            ['salutation.repository', 1, $repository],
            ['country.repository', 1, $repository],
            [Connection::class, 1, $connection],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($order);

        $repository->method('searchIds')->willReturn($idSearchResult);
        $idSearchResult->method('firstId')->willReturn('some-id');

        $connection->method('fetchOne')->willReturn('some-id');

        $factory = new OrderFactory($container);
        $result = $factory->create(Context::createDefaultContext());

        $this->assertInstanceOf(OrderEntity::class, $result);
    }

    public function testWithCustomer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $idSearchResult = $this->createMock(IdSearchResult::class);
        $connection = $this->createMock(Connection::class);

        $container->method('get')->willReturnMap([
            ['salutation.repository', 1, $repository],
            ['country.repository', 1, $repository],
            [Connection::class, 1, $connection],
        ]);

        $repository->method('searchIds')->willReturn($idSearchResult);
        $idSearchResult->method('firstId')->willReturn('some-id');
        $connection->method('fetchOne')->willReturn('some-id');

        $factory = new OrderFactory($container);
        $factory->withCustomer('customer-id');

        $this->assertInstanceOf(OrderFactory::class, $factory);
    }
}
