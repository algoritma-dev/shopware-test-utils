<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'private' => false,
            'mediaFolderId' => null, // Optional: could fetch a default folder
            'alt' => $this->faker->sentence,
            'title' => $this->faker->word,
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'media.repository';
    }
}
