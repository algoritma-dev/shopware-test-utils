<?php

namespace Algoritma\ShopwareTestUtils\Factory\MultiWarehouse;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\MultiWarehouse\Entity\Warehouse\WarehouseEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WarehouseFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->city . ' Warehouse',
            'description' => $this->faker->sentence,
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'warehouse.repository';
    }
}
