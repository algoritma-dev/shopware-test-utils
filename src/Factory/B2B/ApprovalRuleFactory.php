<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\B2B\OrderApproval\Entity\ApprovalRule\ApprovalRuleDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

class ApprovalRuleFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'b2b_approval_rule.repository';
    }

    protected function getEntityName(): string
    {
        return ApprovalRuleDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'priority' => $this->faker->numberBetween(1, 100),
            'active' => true,
            'conditions' => [],
        ];
    }
}
