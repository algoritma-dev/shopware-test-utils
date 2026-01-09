<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PromotionFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'active' => true,
            'useCodes' => false,
            'useSetGroups' => false,
        ];
    }

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

    protected function getEntityClass(): string
    {
        return PromotionEntity::class;
    }
}
