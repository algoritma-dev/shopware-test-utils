<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\SalesChannelFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SalesChannelFactoryTest extends TestCase
{
    public function testCreateSalesChannel(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $idSearchResult = $this->createMock(IdSearchResult::class);
        $salesChannel = new SalesChannelEntity();

        $container->method('get')->willReturn($repository);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($salesChannel);

        $repository->method('searchIds')->willReturn($idSearchResult);
        $idSearchResult->method('firstId')->willReturn('some-id');

        $factory = new SalesChannelFactory($container);
        $result = $factory->create(Context::createDefaultContext());

        $this->assertInstanceOf(SalesChannelEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $idSearchResult = $this->createMock(IdSearchResult::class);

        $container->method('get')->willReturn($repository);
        $repository->method('searchIds')->willReturn($idSearchResult);
        $idSearchResult->method('firstId')->willReturn('some-id');

        $factory = new SalesChannelFactory($container);
        $factory->withName('Test Store');

        $this->assertInstanceOf(SalesChannelFactory::class, $factory);
    }
}
