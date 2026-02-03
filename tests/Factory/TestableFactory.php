<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;

/**
 * @method static self withName(mixed $value)
 * @method static self setName(mixed $value)
 * @method static self withCustomer(mixed $value)
 * @method static self withCustomerNumber(mixed $value)
 * @method static self withCustomerId(mixed $value)
 * @method static self withProduct(mixed $value)
 * @method static self withActive(mixed $value)
 * @method static self setDescription(mixed $value)
 * @method static self withId(mixed $value)
 * @method static self setCategory(mixed $value)
 */
class TestableFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'test.repository';
    }

    protected function getEntityName(): string
    {
        return 'test_entity';
    }

    protected function getDefaults(): array
    {
        return [];
    }
}
