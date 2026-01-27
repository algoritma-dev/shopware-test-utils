<?php

namespace Algoritma\ShopwareTestUtils\Factory\ReturnManagement;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\ReturnManagement\Entity\OrderReturn\OrderReturnDefinition;
use Shopware\Core\Framework\Uuid\Uuid;
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

    public function withOrder(string $orderId): self
    {
        $this->data['orderId'] = $orderId;

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'order_return.repository';
    }

    protected function getEntityName(): string
    {
        return OrderReturnDefinition::ENTITY_NAME;
    }
}
