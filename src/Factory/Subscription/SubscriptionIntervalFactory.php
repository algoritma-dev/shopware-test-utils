<?php

namespace Algoritma\ShopwareTestUtils\Factory\Subscription;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\Subscription\Entity\SubscriptionInterval\SubscriptionIntervalDefinition;

class SubscriptionIntervalFactory extends AbstractFactory
{
    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'subscription_interval.repository';
    }

    protected function getEntityName(): string
    {
        return SubscriptionIntervalDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [];
    }
}
