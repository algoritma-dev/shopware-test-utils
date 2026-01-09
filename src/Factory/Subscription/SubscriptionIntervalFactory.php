<?php

namespace Algoritma\ShopwareTestUtils\Factory\Subscription;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;

class SubscriptionIntervalFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'subscription_interval.repository';
    }
}
