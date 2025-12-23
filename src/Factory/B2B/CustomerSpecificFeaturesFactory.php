<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Shopware\Commercial\B2B\QuickOrder\Entity\CustomerSpecificFeaturesEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomerSpecificFeaturesFactory
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    public function __construct(private readonly ContainerInterface $container) {}

    public function withCustomer(string $customerId): self
    {
        $this->data['customerId'] = $customerId;

        return $this;
    }

    /**
     * @param array<string, mixed> $features
     */
    public function withFeatures(array $features): self
    {
        $this->data['features'] = $features;

        return $this;
    }

    public function create(?Context $context = null): CustomerSpecificFeaturesEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<CustomerSpecificFeaturesEntity> $repository */
        $repository = $this->container->get('b2b_customer_specific_features.repository');

        $repository->create([$this->data], $context);

        /** @var CustomerSpecificFeaturesEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }
}
