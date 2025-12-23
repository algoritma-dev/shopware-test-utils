<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\Subscription;

use Algoritma\ShopwareTestUtils\Factory\Subscription\SubscriptionPlanFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\Subscription\Entity\SubscriptionPlan\SubscriptionPlanEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SubscriptionPlanFactoryTest extends TestCase
{
    public function testCreateSubscriptionPlan(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $plan = new SubscriptionPlanEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($plan);

        $factory = new SubscriptionPlanFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(SubscriptionPlanEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new SubscriptionPlanFactory($container);

        $factory->withName('Test Plan');

        $this->assertInstanceOf(SubscriptionPlanFactory::class, $factory);
    }
}
