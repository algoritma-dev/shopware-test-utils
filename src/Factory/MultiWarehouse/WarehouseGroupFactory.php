<?php

namespace Algoritma\ShopwareTestUtils\Factory\MultiWarehouse;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\MultiWarehouse\Entity\WarehouseGroup\WarehouseGroupEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WarehouseGroupFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(2, true) . ' Group',
            'priority' => $this->faker->numberBetween(1, 100),
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'warehouse_group.repository';
    }

    protected function getEntityClass(): string
    {
        return WarehouseGroupEntity::class;
    }
}
