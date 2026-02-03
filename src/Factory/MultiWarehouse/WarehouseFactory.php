<?php

namespace Algoritma\ShopwareTestUtils\Factory\MultiWarehouse;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\MultiWarehouse\Entity\Warehouse\WarehouseDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

class WarehouseFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'warehouse.repository';
    }

    protected function getEntityName(): string
    {
        return WarehouseDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->city . ' Warehouse',
            'description' => $this->faker->sentence,
        ];
    }
}
