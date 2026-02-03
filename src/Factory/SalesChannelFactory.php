<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\Test\TestDefaults;

class SalesChannelFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'sales_channel.repository';
    }

    protected function getEntityName(): string
    {
        return SalesChannelDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
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

    private function getDefaultPaymentMethodId(): string
    {
        /** @var EntityRepository<PaymentMethodCollection> $repo */
        $repo = $this->container->get('payment_method.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getDefaultShippingMethodId(): string
    {
        /** @var EntityRepository<ShippingMethodCollection> $repo */
        $repo = $this->container->get('shipping_method.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getCountryId(): string
    {
        /** @var EntityRepository<CountryCollection> $repo */
        $repo = $this->container->get('country.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getRootCategoryId(): string
    {
        /** @var EntityRepository<CategoryCollection> $repo */
        $repo = $this->container->get('category.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', null));

        return $repo->searchIds($criteria, Context::createCLIContext())->firstId();
    }
}
