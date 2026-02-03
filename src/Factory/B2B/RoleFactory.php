<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Role\RoleDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

class RoleFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'b2b_components_role.repository';
    }

    protected function getEntityName(): string
    {
        return RoleDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->jobTitle,
            'permissions' => [],
        ];
    }
}
