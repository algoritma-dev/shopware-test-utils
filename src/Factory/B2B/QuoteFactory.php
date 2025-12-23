<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuoteFactory
{
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'quoteNumber' => (string) $this->faker->numberBetween(10000, 99999),
            'expirationDate' => new \DateTime('+30 days'),
            // Add other required fields based on QuoteEntity definition
        ];
    }

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

    public function create(?Context $context = null): QuoteEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get('quote.repository');

        $repository->create([$this->data], $context);

        return $repository->search(new Criteria([$this->data['id']]), $context)->first();
    }
}
