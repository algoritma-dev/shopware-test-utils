<?php

namespace Algoritma\ShopwareTestUtils\Factory\ReturnManagement;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Shopware\Commercial\ReturnManagement\Entity\OrderReturn\OrderReturnDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

class OrderReturnFactory extends AbstractFactory
{
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

    protected function getDefaults(): array
    {
        $this->faker = Factory::create();

        return [
            'id' => Uuid::randomHex(),
            'returnNumber' => (string) $this->faker->numberBetween(10000, 99999),
            'requestedAt' => new \DateTime(),
        ];
    }
}
