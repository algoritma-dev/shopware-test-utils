<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\CustomerFeatureToggleHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\QuickOrder\Entity\CustomerSpecificFeaturesEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomerFeatureToggleHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(CustomerSpecificFeaturesEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testEnableFeature(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $features = new CustomerSpecificFeaturesEntity();
        $features->setId('features-id');

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($features);

        $repository->expects($this->once())->method('upsert');

        $helper = new CustomerFeatureToggleHelper($container);
        $helper->enableFeature('customer-id', 'QUICK_ORDER');
    }

    public function testIsFeatureEnabled(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $features = new CustomerSpecificFeaturesEntity();
        $features->setId('features-id');
        $features->setFeatures(['QUICK_ORDER' => true]);

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($features);

        $helper = new CustomerFeatureToggleHelper($container);
        $result = $helper->isFeatureEnabled('customer-id', 'QUICK_ORDER');

        $this->assertTrue($result);
    }
}
