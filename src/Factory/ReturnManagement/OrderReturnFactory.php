<?php

namespace Algoritma\ShopwareTestUtils\Factory\ReturnManagement;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\ReturnManagement\Entity\OrderReturn\OrderReturnEntity;use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderReturnFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'returnNumber' => (string) $this->faker->numberBetween(10000, 99999),
            'requestedAt' => new \DateTime(),
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'order_return.repository';
    }

    protected function getEntityClass(): string
    {
        return OrderReturnEntity::class;
    }
}
