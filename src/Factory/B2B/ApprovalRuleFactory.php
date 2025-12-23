<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\B2B\OrderApproval\Entity\ApprovalRule\ApprovalRuleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApprovalRuleFactory
{
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => $this->faker->words(3, true),
            'priority' => $this->faker->numberBetween(1, 100),
            'active' => true,
            'conditions' => [],
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function withBusinessPartner(string $customerId): self
    {
        $this->data['businessPartnerCustomerId'] = $customerId;

        return $this;
    }

    public function withConditions(array $conditions): self
    {
        $this->data['conditions'] = $conditions;

        return $this;
    }

    public function create(?Context $context = null): ApprovalRuleEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_approval_rule.repository');

        $repository->create([$this->data], $context);

        return $repository->search(new Criteria([$this->data['id']]), $context)->first();
    }
}
