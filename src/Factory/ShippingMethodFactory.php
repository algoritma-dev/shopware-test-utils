<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShippingMethodFactory
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
            'availabilityRuleId' => $this->getAvailabilityRuleId(),
            'deliveryTimeId' => $this->getDeliveryTimeId(),
            'prices' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'price' => 5.99,
                    'calculation' => 1, // Line item count
                    'quantityStart' => 1,
                ],
            ],
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function create(?Context $context = null): ShippingMethodEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<ShippingMethodEntity> $repository */
        $repository = $this->container->get('shipping_method.repository');

        $repository->create([$this->data], $context);

        /** @var ShippingMethodEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }

    private function getAvailabilityRuleId(): string
    {
        // Fetch any rule, or create a simple "always valid" one if none exist (simplified here)
        /** @var EntityRepository<RuleEntity> $repo */
        $repo = $this->container->get('rule.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getDeliveryTimeId(): string
    {
        /** @var EntityRepository<DeliveryTimeEntity> $repo */
        $repo = $this->container->get('delivery_time.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }
}
