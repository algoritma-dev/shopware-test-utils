<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\B2B\Budget\Entity\Budget\BudgetEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BudgetFactory extends AbstractFactory
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
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'currencyId' => Defaults::CURRENCY,
            'startDate' => new \DateTime(),
            'renewsType' => 'monthly', // Assuming string representation for enum
            'allowApproval' => true,
            'showRemaining' => true,
            'notify' => false,
            'sent' => false,
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'b2b_budget.repository';
    }

    protected function getEntityClass(): string
    {
        return BudgetEntity::class;
    }
}
