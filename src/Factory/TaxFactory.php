<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tax\TaxDefinition;

class TaxFactory extends AbstractFactory
{
    public function withRate(float $rate): self
    {
        $this->data['taxRate'] = $rate;

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'tax.repository';
    }

    protected function getEntityName(): string
    {
        return TaxDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->word . ' Tax',
            'taxRate' => $this->faker->randomFloat(2, 0, 30),
            'position' => 1,
        ];
    }
}
