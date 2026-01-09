<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteDefinition;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuoteFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'quoteNumber' => (string) $this->faker->numberBetween(10000, 99999),
            'expirationDate' => new \DateTime('+30 days'),
            // Add other required fields based on QuoteEntity definition
        ];
    }

    protected function getRepositoryName(): string
    {
        return 'quote.repository';
    }

    protected function getEntityName(): string
    {
        return QuoteDefinition::ENTITY_NAME;
    }
}
