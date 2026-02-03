<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Checkout\Promotion\PromotionDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

class PromotionFactory extends AbstractFactory
{
    public function withCode(string $code): self
    {
        $this->data['useCodes'] = true;
        $this->data['code'] = $code;

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'promotion.repository';
    }

    protected function getEntityName(): string
    {
        return PromotionDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'active' => true,
            'useCodes' => false,
            'useSetGroups' => false,
        ];
    }
}
