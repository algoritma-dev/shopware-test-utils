<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

class RuleFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'rule.repository';
    }

    protected function getEntityName(): string
    {
        return RuleDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'priority' => $this->faker->numberBetween(1, 100),
            'conditions' => [
                [
                    'type' => 'alwaysValid', // A simple condition that is always true
                ],
            ],
        ];
    }
}
