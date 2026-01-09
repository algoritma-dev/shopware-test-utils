<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DefaultPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->creditCardType,
            'active' => true,
            'handlerIdentifier' => DefaultPayment::class, // Standard handler
            'availabilityRuleId' => $this->getAvailabilityRuleId(),
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'payment_method.repository';
    }

    protected function getEntityName(): string
    {
        return PaymentMethodDefinition::ENTITY_NAME;
    }

    private function getAvailabilityRuleId(): string
    {
        /** @var EntityRepository<RuleEntity> $repo */
        $repo = $this->container->get('rule.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }
}
