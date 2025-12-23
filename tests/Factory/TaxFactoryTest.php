<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\TaxFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\Tax\TaxEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaxFactoryTest extends TestCase
{
    public function testCreateTax(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $tax = new TaxEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($tax);

        $factory = new TaxFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(TaxEntity::class, $result);
    }

    public function testWithRate(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new TaxFactory($container);

        $factory->withRate(19.0);

        $this->assertInstanceOf(TaxFactory::class, $factory);
    }
}
