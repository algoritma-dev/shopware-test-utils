<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BudgetFactory
{
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'active' => true,
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'currencyId' => Defaults::CURRENCY,
            'startDate' => new \DateTime(),
            'renewsType' => 'monthly', // Assuming string representation for enum
            'allowApproval' => true,
            'showRemaining' => true,
            'notify' => false,
            'sent' => false,
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function withAmount(float $amount): self
    {
        $this->data['amount'] = $amount;

        return $this;
    }

    public function withCustomer(string $customerId): self
    {
        $this->data['customerId'] = $customerId;

        return $this;
    }

    public function create(?Context $context = null): BudgetEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_budget.repository');

        $repository->create([$this->data], $context);

        return $repository->search(new Criteria([$this->data['id']]), $context)->first();
    }
}
