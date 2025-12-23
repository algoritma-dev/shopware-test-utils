<?php

namespace Algoritma\ShopwareTestUtils\Factory\MultiWarehouse;

use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\MultiWarehouse\Entity\Warehouse\WarehouseEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WarehouseFactory
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
            'name' => $this->faker->city . ' Warehouse',
            'description' => $this->faker->sentence,
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function create(?Context $context = null): WarehouseEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<WarehouseEntity> $repository */
        $repository = $this->container->get('warehouse.repository');

        $repository->create([$this->data], $context);

        /** @var WarehouseEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }
}
