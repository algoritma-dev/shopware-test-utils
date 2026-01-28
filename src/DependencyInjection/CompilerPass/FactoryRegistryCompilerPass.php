<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\DependencyInjection\CompilerPass;

use Algoritma\ShopwareTestUtils\Core\FactoryRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FactoryRegistryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has(FactoryRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(FactoryRegistry::class);

        foreach (array_keys($container->findTaggedServiceIds(FactoryRegistry::TAG)) as $id) {
            $definition = $container->findDefinition($id);
            $className = $definition->getClass();
            if ($className === null) {
                continue;
            }

            $className = $container->getParameterBag()->resolveValue($className);
            if (! is_string($className)) {
                continue;
            }
            if ($className === '') {
                continue;
            }

            $registryDefinition->addMethodCall('registerFactoryClass', [$className]);
        }
    }
}
