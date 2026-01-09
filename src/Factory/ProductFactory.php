<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->faker = Factory::create();

        $this->data = [
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

    protected function getEntityClass(): string
    {
        return \Shopware\Core\Content\Product\ProductEntity::class;
    }
}
