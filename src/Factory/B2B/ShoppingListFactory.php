<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListDefinition;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShoppingListFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'active' => true,
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'b2b_shopping_list.repository';
    }

    protected function getEntityName(): string
    {
        return ShoppingListDefinition::ENTITY_NAME;
    }
}
