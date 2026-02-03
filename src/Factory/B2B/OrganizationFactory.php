<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Core\Framework\Uuid\Uuid;

class OrganizationFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'b2b_components_organization.repository';
    }

    protected function getEntityName(): string
    {
        return 'b2b_components_organization';
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->company,
        ];
    }
}
