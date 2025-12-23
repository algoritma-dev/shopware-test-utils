<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\PromotionFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PromotionFactoryTest extends TestCase
{
    public function testCreatePromotion(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $promotion = new PromotionEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($promotion);

        $factory = new PromotionFactory($container);
        $result = $factory->create(Context::createDefaultContext());

        $this->assertInstanceOf(PromotionEntity::class, $result);
    }

    public function testWithCode(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new PromotionFactory($container);

        $factory->withCode('PROMO123');

        $this->assertInstanceOf(PromotionFactory::class, $factory);
    }
}
