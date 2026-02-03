<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductFactory extends AbstractFactory
{
    public function withPrice(float $gross, ?float $net = null): self
    {
        if ($net === null) {
            $net = $gross / 1.19;
        }
        $this->data['price'] = [['currencyId' => Defaults::CURRENCY, 'gross' => $gross, 'net' => $net, 'linked' => false]];

        return $this;
    }

    public function active(bool $active = true): self
    {
        $this->data['active'] = $active;

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'product.repository';
    }

    protected function getEntityName(): string
    {
        return ProductDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'productNumber' => Uuid::randomHex(),
            'stock' => $this->faker->numberBetween(1, 100),
            'name' => $this->faker->word,
            'description' => $this->faker->paragraph,
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 19.99, 'net' => 16.80, 'linked' => false]],
            'tax' => ['name' => 'Standard Rate', 'taxRate' => 19],
            'manufacturer' => ['name' => $this->faker->company],
            'active' => true,
        ];
    }
}
