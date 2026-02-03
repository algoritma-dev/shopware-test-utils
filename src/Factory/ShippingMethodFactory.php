<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Content\Rule\RuleCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\DeliveryTime\DeliveryTimeCollection;

class ShippingMethodFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'shipping_method.repository';
    }

    protected function getEntityName(): string
    {
        return ShippingMethodDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
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

    private function getAvailabilityRuleId(): string
    {
        // Fetch any rule, or create a simple "always valid" one if none exist (simplified here)
        /** @var EntityRepository<RuleCollection> $repo */
        $repo = $this->container->get('rule.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getDeliveryTimeId(): string
    {
        /** @var EntityRepository<DeliveryTimeCollection> $repo */
        $repo = $this->container->get('delivery_time.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }
}
