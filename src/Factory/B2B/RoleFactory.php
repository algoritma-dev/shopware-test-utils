<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RoleFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->jobTitle,
            'permissions' => [],
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'b2b_role.repository';
    }
}
