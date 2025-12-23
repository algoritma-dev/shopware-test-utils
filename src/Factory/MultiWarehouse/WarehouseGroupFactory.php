<?php

namespace Algoritma\ShopwareTestUtils\Factory\MultiWarehouse;

use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\MultiWarehouse\Entity\WarehouseGroup\WarehouseGroupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WarehouseGroupFactory
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
            'name' => $this->faker->words(2, true) . ' Group',
            'priority' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function withPriority(int $priority): self
    {
        $this->data['priority'] = $priority;

        return $this;
    }

    public function withRule(string $ruleId): self
    {
        $this->data['ruleId'] = $ruleId;

        return $this;
    }

    public function create(?Context $context = null): WarehouseGroupEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<WarehouseGroupEntity> $repository */
        $repository = $this->container->get('warehouse_group.repository');

        $repository->create([$this->data], $context);

        /** @var WarehouseGroupEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }
}
