<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils;

use Algoritma\ShopwareTestUtils\DependencyInjection\CompilerPass\FactoryRegistryCompilerPass;
use Shopware\Core\Framework\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ShopwareTestUtils extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new FactoryRegistryCompilerPass());
    }
}
