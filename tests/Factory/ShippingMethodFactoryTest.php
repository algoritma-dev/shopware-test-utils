<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\ShippingMethodFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShippingMethodFactoryTest extends TestCase
{
    public function testCreateShippingMethod(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $idSearchResult = $this->createStub(IdSearchResult::class);
        $shippingMethod = new ShippingMethodEntity();

        $container->method('get')->willReturnMap([
            ['shipping_method.repository', 1, $repository],
            ['rule.repository', 1, $repository],
            ['delivery_time.repository', 1, $repository],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($shippingMethod);

        $repository->method('searchIds')->willReturn($idSearchResult);
        $idSearchResult->method('firstId')->willReturn('some-id');

        $factory = new ShippingMethodFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(ShippingMethodEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $idSearchResult = $this->createStub(IdSearchResult::class);

        $container->method('get')->willReturn($repository);
        $repository->method('searchIds')->willReturn($idSearchResult);
        $idSearchResult->method('firstId')->willReturn('some-id');

        $factory = new ShippingMethodFactory($container);
        $factory->withName('Test Shipping');

        $this->assertInstanceOf(ShippingMethodFactory::class, $factory);
    }
}
