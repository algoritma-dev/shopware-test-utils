<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\B2B\QuickOrder\Entity\CustomerSpecificFeaturesDefinition;

class CustomerSpecificFeaturesFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'b2b_customer_specific_features.repository';
    }

    protected function getEntityName(): string
    {
        return CustomerSpecificFeaturesDefinition::ENTITY_NAME;
    }
}
