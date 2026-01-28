<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Core;

use Algoritma\ShopwareTestUtils\Core\FactoryRegistry;
use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use PHPUnit\Framework\TestCase;

class FactoryRegistryTest extends TestCase
{
    public function testRegisterFactoryClassStoresUniqueSortedClasses(): void
    {
        $registry = new FactoryRegistry();

        $registry->registerFactoryClass(RegistryFactoryB::class);
        $registry->registerFactoryClass(RegistryFactoryA::class);
        $registry->registerFactoryClass(RegistryFactoryA::class);

        $this->assertSame(
            [RegistryFactoryA::class, RegistryFactoryB::class],
            $registry->getFactories()
        );
    }
}

class RegistryFactoryA extends AbstractFactory
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

class RegistryFactoryB extends AbstractFactory
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
