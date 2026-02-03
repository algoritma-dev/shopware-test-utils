<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DefaultPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Content\Rule\RuleCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;

class PaymentMethodFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'payment_method.repository';
    }

    protected function getEntityName(): string
    {
        return PaymentMethodDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->creditCardType,
            'active' => true,
            'handlerIdentifier' => DefaultPayment::class, // Standard handler
            'availabilityRuleId' => $this->getAvailabilityRuleId(),
        ];
    }

    private function getAvailabilityRuleId(): string
    {
        /** @var EntityRepository<RuleCollection> $repo */
        $repo = $this->container->get('rule.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }
}
