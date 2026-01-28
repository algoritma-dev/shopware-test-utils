<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\DependencyInjection;

use Algoritma\ShopwareTestUtils\Core\FactoryRegistry;
use Algoritma\ShopwareTestUtils\DependencyInjection\CompilerPass\FactoryRegistryCompilerPass;
use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class FactoryRegistryCompilerPassTest extends TestCase
{
    public function testCompilerPassRegistersTaggedFactories(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(FactoryRegistry::class, new Definition(FactoryRegistry::class));

        $container->setDefinition('factory.a', new Definition(CompilerFactoryA::class))
            ->addTag(FactoryRegistry::TAG);

        $container->setParameter('test.factory_class', CompilerFactoryB::class);
        $container->setDefinition('factory.b', new Definition('%test.factory_class%'))
            ->addTag(FactoryRegistry::TAG);

        $pass = new FactoryRegistryCompilerPass();
        $pass->process($container);

        $calls = $container->getDefinition(FactoryRegistry::class)->getMethodCalls();
        $classes = [];
        foreach ($calls as $call) {
            if ($call[0] !== 'registerFactoryClass') {
                continue;
            }
            $classes[] = $call[1][0];
        }

        sort($classes);

        $this->assertSame([CompilerFactoryA::class, CompilerFactoryB::class], $classes);
    }
}

class CompilerFactoryA extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'product.repository';
    }

    protected function getEntityName(): string
    {
        return 'product';
    }
}

class CompilerFactoryB extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'product.repository';
    }

    protected function getEntityName(): string
    {
        return 'product';
    }
}
