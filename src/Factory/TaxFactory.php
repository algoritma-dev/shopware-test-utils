<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tax\TaxEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaxFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->word . ' Tax',
            'taxRate' => $this->faker->randomFloat(2, 0, 30),
            'position' => 1,
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'tax.repository';
    }

    protected function getEntityClass(): string
    {
        return TaxEntity::class;
    }
}
