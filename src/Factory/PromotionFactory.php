<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PromotionFactory
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'active' => true,
            'useCodes' => false,
            'useSetGroups' => false,
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function withCode(string $code): self
    {
        $this->data['useCodes'] = true;
        $this->data['code'] = $code;

        return $this;
    }

    public function create(?Context $context = null): PromotionEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<PromotionEntity> $repository */
        $repository = $this->container->get('promotion.repository');

        $repository->create([$this->data], $context);

        /** @var PromotionEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }
}
