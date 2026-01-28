<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Core;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;

class FactoryRegistry
{
    /**
     * @phpstan-ignore-next-line
     */
    public const TAG = 'algoritma.shopware_test_utils.factory';

    /**
     * @var array<class-string<AbstractFactory>, true>
     */
    private array $factories = [];

    /**
     * @param class-string<AbstractFactory> $className
     */
    public function registerFactoryClass(string $className): void
    {
        $this->factories[$className] = true;
    }

    /**
     * @return list<class-string<AbstractFactory>>
     */
    public function getFactories(): array
    {
        $factories = array_keys($this->factories);
        sort($factories);

        return $factories;
    }
}
