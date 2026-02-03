<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class BudgetFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'b2b_budget.repository';
    }

    protected function getEntityName(): string
    {
        return BudgetDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
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
}
