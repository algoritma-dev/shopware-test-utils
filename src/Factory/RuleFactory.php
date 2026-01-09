<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RuleFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->faker = Factory::create();

        $this->data = [
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

    protected function getRepositoryName(): string
    {
        return 'rule.repository';
    }

    protected function getEntityClass(): string
    {
        return \Shopware\Core\Content\Rule\RuleEntity::class;
    }
}
