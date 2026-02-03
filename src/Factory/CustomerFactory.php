<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\Test\TestDefaults;

class CustomerFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'customer.repository';
    }

    protected function getEntityName(): string
    {
        return CustomerDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        $addressId = Uuid::randomHex();
        $salutationId = $this->getSalutationId();

        return [
            'id' => Uuid::randomHex(),
            'customerNumber' => (string) $this->faker->numberBetween(10000, 99999),
            'salutationId' => $salutationId,
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'shopware',
            'defaultPaymentMethodId' => $this->getDefaultPaymentMethodId(),
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT, // Placeholder
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'salutationId' => $salutationId,
                    'firstName' => $this->faker->firstName,
                    'lastName' => $this->faker->lastName,
                    'street' => $this->faker->streetAddress,
                    'zipcode' => $this->faker->postcode,
                    'city' => $this->faker->city,
                    'countryId' => $this->getCountryId(),
                ],
            ],
        ];
    }

    private function getSalutationId(): string
    {
        /** @var EntityRepository<SalutationCollection> $repo */
        $repo = $this->container->get('salutation.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getCountryId(): string
    {
        /** @var EntityRepository<CountryCollection> $repo */
        $repo = $this->container->get('country.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getDefaultPaymentMethodId(): string
    {
        /** @var EntityRepository<PaymentMethodCollection> $repo */
        $repo = $this->container->get('payment_method.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }
}
