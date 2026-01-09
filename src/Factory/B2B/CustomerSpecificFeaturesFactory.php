<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;

class CustomerSpecificFeaturesFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'b2b_customer_specific_features.repository';
    }
}
