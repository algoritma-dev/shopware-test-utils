<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

class ShoppingListFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'b2b_shopping_list.repository';
    }

    protected function getEntityName(): string
    {
        return ShoppingListDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'active' => true,
        ];
    }
}
