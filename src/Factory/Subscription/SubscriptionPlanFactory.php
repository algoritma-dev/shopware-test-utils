<?php

namespace Algoritma\ShopwareTestUtils\Factory\Subscription;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\Subscription\Entity\SubscriptionPlan\SubscriptionPlanDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

class SubscriptionPlanFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'subscription_plan.repository';
    }

    protected function getEntityName(): string
    {
        return SubscriptionPlanDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'active' => true,
            'discountPercentage' => $this->faker->randomFloat(2, 0, 20),
            'minimumExecutionCount' => 0,
        ];
    }
}
