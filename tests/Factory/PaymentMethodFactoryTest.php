<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\PaymentMethodFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodFactoryTest extends TestCase
{
    public function testCreatePaymentMethod(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $idSearchResult = $this->createMock(IdSearchResult::class);
        $paymentMethod = new PaymentMethodEntity();

        $container->method('get')->willReturnMap([
            ['payment_method.repository', 1, $repository],
            ['rule.repository', 1, $repository],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($paymentMethod);

        $repository->method('searchIds')->willReturn($idSearchResult);
        $idSearchResult->method('firstId')->willReturn('some-id');

        $factory = new PaymentMethodFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(PaymentMethodEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $idSearchResult = $this->createMock(IdSearchResult::class);

        $container->method('get')->willReturn($repository);
        $repository->method('searchIds')->willReturn($idSearchResult);
        $idSearchResult->method('firstId')->willReturn('some-id');

        $factory = new PaymentMethodFactory($container);
        $factory->withName('Test Payment');

        $this->assertInstanceOf(PaymentMethodFactory::class, $factory);
    }
}
