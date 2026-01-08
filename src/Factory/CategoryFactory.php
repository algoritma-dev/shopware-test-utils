<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CategoryFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->word,
            'active' => true,
            'displayNestedProducts' => true,
            'type' => 'page',
        ];
    }

    public function active(bool $active = true): self
    {
        $this->data['active'] = $active;

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'category.repository';
    }
}
