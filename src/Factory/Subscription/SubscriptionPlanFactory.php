<?php

namespace Algoritma\ShopwareTestUtils\Factory\Subscription;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\Subscription\Entity\SubscriptionPlan\SubscriptionPlanEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SubscriptionPlanFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'active' => true,
            'discountPercentage' => $this->faker->randomFloat(2, 0, 20),
            'minimumExecutionCount' => 0,
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'subscription_plan.repository';
    }

    protected function getEntityClass(): string
    {
        return SubscriptionPlanEntity::class;
    }
}
