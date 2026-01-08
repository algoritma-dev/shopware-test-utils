<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\B2B\QuickOrder\Entity\CustomerSpecificFeaturesEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomerSpecificFeaturesFactory extends AbstractFactory
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    protected function getRepositoryName(): string
    {
        return 'b2b_customer_specific_features.repository';
    }
}
