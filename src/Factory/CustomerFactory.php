<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Doctrine\DBAL\Connection;
use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\Test\TestDefaults;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomerFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->faker = Factory::create();

        $addressId = Uuid::randomHex();
        $salutationId = $this->getSalutationId();

        $this->data = [
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

    protected function getRepositoryName(): string
    {
        return 'customer.repository';
    }

    private function getSalutationId(): string
    {
        /** @var EntityRepository<SalutationEntity> $repo */
        $repo = $this->container->get('salutation.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getCountryId(): string
    {
        /** @var EntityRepository<CountryEntity> $repo */
        $repo = $this->container->get('country.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getDefaultPaymentMethodId(): string
    {
        /** @var EntityRepository<PaymentMethodEntity> $repo */
        $repo = $this->container->get('payment_method.repository');

        return $repo->searchIds(new Criteria(), Context::createCLIContext())->firstId();
    }

    private function getSalesChannelId(): string
    {
        $connection = $this->container->get(Connection::class);
        $id = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM sales_channel LIMIT 1');

        return $id ?: Uuid::randomHex();
    }
}
