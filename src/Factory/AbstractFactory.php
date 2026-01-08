<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base factory class with common functionality for all entity factories.
 */
abstract class AbstractFactory
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function __construct(protected readonly ContainerInterface $container) {}

    /**
     * Magic method to handle dynamic with*() and set*() calls.
     *
     * Examples:
     * - withCustomerNumber('123') → sets 'customerNumber'
     * - setProductNumber('ABC') → sets 'productNumber'
     * - withEan('1234567890') → sets 'ean'
     * - withCustomer('uuid') → sets 'customerId' (automatically adds 'Id' suffix when value is UUID)
     * - withProduct('uuid') → sets 'productId'
     * - withSalesChannel('uuid') → sets 'salesChannelId'
     */
    public function __call(string $method, array $arguments): static
    {
        // Check for with* pattern
        if (str_starts_with($method, 'with')) {
            $property = lcfirst(substr($method, 4));
            $value = $arguments[0] ?? null;

            // Auto-append 'Id' suffix if the value is a UUID and property doesn't already end with 'Id'
            if ($this->shouldAppendIdSuffix($property, $value)) {
                $property .= 'Id';
            }

            $this->data[$property] = $value;

            return $this;
        }

        // Check for set* pattern
        if (str_starts_with($method, 'set')) {
            $property = lcfirst(substr($method, 3));
            $value = $arguments[0] ?? null;

            // Same logic for set* methods
            if ($this->shouldAppendIdSuffix($property, $value)) {
                $property .= 'Id';
            }

            $this->data[$property] = $value;

            return $this;
        }

        throw new \BadMethodCallException(sprintf('Method %s::%s does not exist', static::class, $method));
    }

    /**
     * Get the raw data array (useful for debugging or custom modifications).
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Create and persist the entity.
     */
    public function create(?Context $context = null): Entity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get($this->getRepositoryName());

        $repository->create([$this->data], $context);

        /** @var Entity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }

    /**
     * Get the repository name (e.g., 'product.repository').
     */
    abstract protected function getRepositoryName(): string;

    /**
     * Determines if 'Id' suffix should be appended to a property name.
     *
     * Returns true if the value is a valid UUID and the property doesn't already end with 'Id'
     */
    private function shouldAppendIdSuffix(string $property, mixed $value): bool
    {
        // Don't append if property already ends with 'Id' or 'Ids'
        if (str_ends_with($property, 'Id') || str_ends_with($property, 'Ids')) {
            return false;
        }

        // Check if value is a valid UUID (with or without dashes)
        if (! is_string($value)) {
            return false;
        }

        // UUID format: 8-4-4-4-12 hex characters (with or without dashes)
        // Examples:
        // - 550e8400-e29b-41d4-a716-446655440000 (with dashes)
        // - 550e8400e29b41d4a716446655440000 (without dashes)
        $uuidPattern = '/^[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}$/i';

        return \preg_match($uuidPattern, $value) === 1;
    }
}
