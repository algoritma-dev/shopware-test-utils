<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\TestDefaults;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SalesChannelFactory
{
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'name' => $this->faker->company . ' Store',
            'accessKey' => Uuid::randomHex(),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'currencyId' => Defaults::CURRENCY,
            'paymentMethodId' => $this->getDefaultPaymentMethodId(),
            'shippingMethodId' => $this->getDefaultShippingMethodId(),
            'countryId' => $this->getCountryId(),
            'navigationCategoryId' => $this->getRootCategoryId(),
            'customerGroupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
            'currencies' => [['id' => Defaults::CURRENCY]],
        ];
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function create(?Context $context = null): SalesChannelEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get('sales_channel.repository');

        $repository->create([$this->data], $context);

        return $repository->search(new Criteria([$this->data['id']]), $context)->first();
    }

    private function getDefaultPaymentMethodId(): string
    {
        $repo = $this->container->get('payment_method.repository');

        return $repo->searchIds(new Criteria(), Context::createDefaultContext())->firstId();
    }

    private function getDefaultShippingMethodId(): string
    {
        $repo = $this->container->get('shipping_method.repository');

        return $repo->searchIds(new Criteria(), Context::createDefaultContext())->firstId();
    }

    private function getCountryId(): string
    {
        $repo = $this->container->get('country.repository');

        return $repo->searchIds(new Criteria(), Context::createDefaultContext())->firstId();
    }

    private function getRootCategoryId(): string
    {
        $repo = $this->container->get('category.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', null));

        return $repo->searchIds($criteria, Context::createDefaultContext())->firstId();
    }
}
