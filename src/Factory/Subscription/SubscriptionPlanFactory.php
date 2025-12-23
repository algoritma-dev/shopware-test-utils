<?php

namespace Algoritma\ShopwareTestUtils\Factory\Subscription;

use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\Subscription\Entity\SubscriptionPlan\SubscriptionPlanEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SubscriptionPlanFactory
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
            'discountPercentage' => $this->faker->randomFloat(2, 0, 20),
            'minimumExecutionCount' => 0,
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function withDiscount(float $percentage): self
    {
        $this->data['discountPercentage'] = $percentage;

        return $this;
    }

    public function create(?Context $context = null): SubscriptionPlanEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<SubscriptionPlanEntity> $repository */
        $repository = $this->container->get('subscription_plan.repository');

        $repository->create([$this->data], $context);

        /** @var SubscriptionPlanEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }
}
