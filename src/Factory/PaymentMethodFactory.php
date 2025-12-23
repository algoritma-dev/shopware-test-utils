<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DefaultPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodFactory
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
            'name' => $this->faker->creditCardType,
            'active' => true,
            'handlerIdentifier' => DefaultPayment::class, // Standard handler
            'availabilityRuleId' => $this->getAvailabilityRuleId(),
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function create(?Context $context = null): PaymentMethodEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<PaymentMethodEntity> $repository */
        $repository = $this->container->get('payment_method.repository');

        $repository->create([$this->data], $context);

        /** @var PaymentMethodEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }

    private function getAvailabilityRuleId(): string
    {
        /** @var EntityRepository<RuleEntity> $repo */
        $repo = $this->container->get('rule.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }
}
