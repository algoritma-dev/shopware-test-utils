<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\B2B\AdvancedProductCatalogs\Entity\AdvancedProductCatalogs\AdvancedProductCatalogsDefinition;

class AdvancedProductCatalogFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'b2b_advanced_product_catalogs.repository';
    }

    protected function getEntityName(): string
    {
        return AdvancedProductCatalogsDefinition::ENTITY_NAME;
    }
}
