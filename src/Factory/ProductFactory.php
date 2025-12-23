<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductFactory
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
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

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function withPrice(float $gross, ?float $net = null): self
    {
        if ($net === null) {
            $net = $gross / 1.19;
        }
        $this->data['price'] = [['currencyId' => Defaults::CURRENCY, 'gross' => $gross, 'net' => $net, 'linked' => false]];

        return $this;
    }

    public function withStock(int $stock): self
    {
        $this->data['stock'] = $stock;

        return $this;
    }

    public function active(bool $active = true): self
    {
        $this->data['active'] = $active;

        return $this;
    }

    public function create(?Context $context = null): ProductEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<ProductEntity> $repository */
        $repository = $this->container->get('product.repository');

        $repository->create([$this->data], $context);

        /** @var ProductEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }
}
