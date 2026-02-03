<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

class CategoryFactory extends AbstractFactory
{
    public function active(bool $active = true): self
    {
        $this->data['active'] = $active;

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'category.repository';
    }

    protected function getEntityName(): string
    {
        return CategoryDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->word,
            'active' => true,
            'displayNestedProducts' => true,
            'type' => 'page',
        ];
    }
}
