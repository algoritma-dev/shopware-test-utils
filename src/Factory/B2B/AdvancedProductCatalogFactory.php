<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdvancedProductCatalogFactory extends AbstractFactory
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    protected function getRepositoryName(): string
    {
        return 'b2b_advanced_product_catalogs.repository';
    }
}
