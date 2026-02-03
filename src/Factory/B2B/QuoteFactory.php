<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteDefinition;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Core\Framework\Uuid\Uuid;

class QuoteFactory extends AbstractFactory
{
    public function withCustomer(string $customerId): self
    {
        $this->data['customerId'] = $customerId;

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'quote.repository';
    }

    protected function getEntityName(): string
    {
        return QuoteDefinition::ENTITY_NAME;
    }

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'quoteNumber' => (string) $this->faker->numberBetween(10000, 99999),
            'expirationDate' => new \DateTime('+30 days'),
            // Add other required fields based on QuoteEntity definition
        ];
    }
}
