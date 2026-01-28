<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils;

use Algoritma\ShopwareTestUtils\DependencyInjection\CompilerPass\FactoryRegistryCompilerPass;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ShopwareTestUtils extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new FactoryRegistryCompilerPass());
    }
}
