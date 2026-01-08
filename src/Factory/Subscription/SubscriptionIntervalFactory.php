<?php

namespace Algoritma\ShopwareTestUtils\Factory\Subscription;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SubscriptionIntervalFactory extends AbstractFactory
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    protected function getRepositoryName(): string
    {
        return 'subscription_interval.repository';
    }
}
