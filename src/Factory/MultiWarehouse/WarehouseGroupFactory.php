<?php

namespace Algoritma\ShopwareTestUtils\Factory\MultiWarehouse;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\MultiWarehouse\Entity\WarehouseGroup\WarehouseGroupDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

class WarehouseGroupFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'warehouse_group.repository';
    }

    protected function getEntityName(): string
    {
        return WarehouseGroupDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(2, true) . ' Group',
            'priority' => $this->faker->numberBetween(1, 100),
        ];
    }
}
