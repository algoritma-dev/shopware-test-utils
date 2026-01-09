<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Doctrine\DBAL\Connection;
use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->faker = Factory::create();

        $addressId = Uuid::randomHex();
        $stateId = $this->getStateId('order.state', 'open');

        $this->data = [
            'id' => Uuid::randomHex(),
            'orderNumber' => (string) $this->faker->numberBetween(10000, 99999),
            'billingAddressId' => $addressId,
            'currencyId' => Defaults::CURRENCY,
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'salesChannelId' => $this->getSalesChannelId(),
            'orderDateTime' => (new \DateTime())->format(\DateTime::ATOM),
            'price' => new CartPrice(
                100, 100, 100, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS
            ),
            'shippingCosts' => new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'stateId' => $stateId,
            'currencyFactor' => 1.0,
            // Mocking required associations usually handled by CartService
            'addresses' => [
                [
                    'id' => $addressId,
                    'salutationId' => $this->getSalutationId(),
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

    public function withCustomer(string $customerId): self
    {
        $this->data['orderCustomer'] = [
            'customerId' => $customerId,
            'email' => $this->faker->email,
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
        ];

        return $this;
    }

    public function withLineItem(string $productId, int $quantity = 1, float $price = 19.99): self
    {
        if (! isset($this->data['lineItems'])) {
            $this->data['lineItems'] = [];
        }

        $this->data['lineItems'][] = [
            'id' => Uuid::randomHex(),
            'identifier' => $productId,
            'referencedId' => $productId,
            'label' => 'Test Product',
            'quantity' => $quantity,
            'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
            'price' => new CalculatedPrice(
                $price,
                $price * $quantity,
                new CalculatedTaxCollection(),
                new TaxRuleCollection()
            ),
            'priceDefinition' => null,
        ];

        return $this;
    }

    public function withState(string $stateName): self
    {
        $this->data['stateId'] = $this->getStateId('order.state', $stateName);

        return $this;
    }

    public function withPaymentMethod(string $paymentMethodId): self
    {
        if (! isset($this->data['transactions'])) {
            $this->data['transactions'] = [];
        }

        $this->data['transactions'][] = [
            'id' => Uuid::randomHex(),
            'paymentMethodId' => $paymentMethodId,
            'stateId' => $this->getStateId('order_transaction.state', 'open'),
            'amount' => new CalculatedPrice(
                100,
                100,
                new CalculatedTaxCollection(),
                new TaxRuleCollection()
            ),
        ];

        return $this;
    }

    public function withShippingMethod(string $shippingMethodId): self
    {
        if (! isset($this->data['deliveries'])) {
            $this->data['deliveries'] = [];
        }

        $addressId = Uuid::randomHex();
        $this->data['deliveries'][] = [
            'id' => Uuid::randomHex(),
            'shippingMethodId' => $shippingMethodId,
            'shippingDateEarliest' => (new \DateTime())->format(\DateTime::ATOM),
            'shippingDateLatest' => (new \DateTime('+3 days'))->format(\DateTime::ATOM),
            'stateId' => $this->getStateId('order_delivery.state', 'open'),
            'shippingCosts' => new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'shippingOrderAddress' => [
                'id' => $addressId,
                'salutationId' => $this->getSalutationId(),
                'firstName' => $this->faker->firstName,
                'lastName' => $this->faker->lastName,
                'street' => $this->faker->streetAddress,
                'zipcode' => $this->faker->postcode,
                'city' => $this->faker->city,
                'countryId' => $this->getCountryId(),
            ],
        ];

        return $this;
    }

    public function withDeliveryDate(\DateTime $date): self
    {
        $this->data['orderDateTime'] = $date->format(\DateTime::ATOM);

        return $this;
    }

    public function withPrice(float $total): self
    {
        $this->data['price'] = new CartPrice(
            $total, $total, $total, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS
        );

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'order.repository';
    }

    protected function getEntityName(): string
    {
        return OrderDefinition::ENTITY_NAME;
    }

    private function getSalesChannelId(): string
    {
        $connection = $this->container->get(Connection::class);
        $id = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM sales_channel LIMIT 1');

        return $id ?: Uuid::randomHex();
    }

    private function getStateId(string $machine, string $place): string
    {
        $connection = $this->container->get(Connection::class);
        $sql = <<<'EOD'

                        SELECT LOWER(HEX(state_machine_state.id))
                        FROM state_machine_state
                        JOIN state_machine ON state_machine.id = state_machine_state.state_machine_id
                        WHERE state_machine.technical_name = :machine AND state_machine_state.technical_name = :place
                    
            EOD;
        $id = $connection->fetchOne($sql, ['machine' => $machine, 'place' => $place]);

        if (! $id) {
            // Fallback or throw exception
            return Uuid::randomHex();
        }

        return $id;
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
}
