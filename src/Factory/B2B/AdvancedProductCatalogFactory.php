<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Shopware\Commercial\B2B\AdvancedProductCatalogs\Entity\AdvancedProductCatalogs\AdvancedProductCatalogsEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdvancedProductCatalogFactory
{
    private array $data;

    public function __construct(private readonly ContainerInterface $container) {}

    public function withCustomer(string $customerId): self
    {
        $this->data['customerId'] = $customerId;

        return $this;
    }

    public function withSalesChannel(string $salesChannelId): self
    {
        $this->data['salesChannelId'] = $salesChannelId;

        return $this;
    }

    public function create(?Context $context = null): AdvancedProductCatalogsEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_advanced_product_catalogs.repository');

        $repository->create([$this->data], $context);

        return $repository->search(new Criteria([$this->data['id']]), $context)->first();
    }
}
