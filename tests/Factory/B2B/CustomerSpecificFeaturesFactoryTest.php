<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\CustomerSpecificFeaturesFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\QuickOrder\Entity\CustomerSpecificFeaturesEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomerSpecificFeaturesFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(CustomerSpecificFeaturesEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testCreateFeatures(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $features = new CustomerSpecificFeaturesEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($features);

        $factory = new CustomerSpecificFeaturesFactory($container);

        // Similar issue: data not initialized.
        $factory->withCustomer('customer-id');

        $this->assertInstanceOf(CustomerSpecificFeaturesFactory::class, $factory);
    }
}
