<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\Test\TestDefaults;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SalesChannelFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
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

    protected function getRepositoryName(): string
    {
        return 'sales_channel.repository';
    }

    protected function getEntityName(): string
    {
        return SalesChannelDefinition::ENTITY_NAME;
    }

    private function getDefaultPaymentMethodId(): string
    {
        /** @var EntityRepository<PaymentMethodEntity> $repo */
        $repo = $this->container->get('payment_method.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getDefaultShippingMethodId(): string
    {
        /** @var EntityRepository<ShippingMethodEntity> $repo */
        $repo = $this->container->get('shipping_method.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getCountryId(): string
    {
        /** @var EntityRepository<CountryEntity> $repo */
        $repo = $this->container->get('country.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getRootCategoryId(): string
    {
        /** @var EntityRepository<CategoryEntity> $repo */
        $repo = $this->container->get('category.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', null));

        return $repo->searchIds($criteria, Context::createCLIContext())->firstId();
    }
}
