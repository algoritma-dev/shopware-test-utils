<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApprovalRuleFactory extends AbstractFactory
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
            'active' => true,
            'conditions' => [],
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'b2b_approval_rule.repository';
    }
}
