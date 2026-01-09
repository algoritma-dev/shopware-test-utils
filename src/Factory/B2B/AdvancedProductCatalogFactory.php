<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;

class AdvancedProductCatalogFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'b2b_advanced_product_catalogs.repository';
    }

    protected function getEntityClass(): string
    {
        return \Shopware\Commercial\B2B\AdvancedProductCatalog\Entity\AdvancedProductCatalog\AdvancedProductCatalogEntity::class;
    }
}
