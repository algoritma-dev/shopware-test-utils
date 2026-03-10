<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @method self withTitle(string $title)
 * @method self withAlt(string $alt)
 */
class MediaFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'media.repository';
    }

    protected function getEntityName(): string
    {
        return MediaDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'private' => false,
            'mediaFolderId' => null, // Optional: could fetch a default folder
            'alt' => $this->faker->sentence,
            'title' => $this->faker->word,
        ];
    }
}
