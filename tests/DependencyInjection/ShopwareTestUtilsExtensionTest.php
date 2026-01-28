<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\DependencyInjection;

use Algoritma\ShopwareTestUtils\Core\FactoryRegistry;
use Algoritma\ShopwareTestUtils\DependencyInjection\ShopwareTestUtilsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ShopwareTestUtilsExtensionTest extends TestCase
{
    public function testLoadsFactoryRegistryService(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new ShopwareTestUtilsExtension();
        $extension->load([], $container);

        $this->assertTrue($container->has(FactoryRegistry::class));
        $this->assertSame([], $container->findTaggedServiceIds(FactoryRegistry::TAG));
    }

    public function testLoadsTaggedFactoriesInTestEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension = new ShopwareTestUtilsExtension();
        $extension->load([], $container);

        $tagged = $container->findTaggedServiceIds(FactoryRegistry::TAG);

        $this->assertNotEmpty($tagged);
    }
}
