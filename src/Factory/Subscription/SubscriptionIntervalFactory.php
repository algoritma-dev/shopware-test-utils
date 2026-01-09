<?php

namespace Algoritma\ShopwareTestUtils\Factory\Subscription;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\Subscription\Entity\SubscriptionInterval\SubscriptionIntervalEntity;

class SubscriptionIntervalFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'subscription_interval.repository';
    }

    protected function getEntityClass(): string
    {
        return SubscriptionIntervalEntity::class;
    }
}
