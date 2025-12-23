<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\QuoteFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuoteFactoryTest extends TestCase
{
    public function testCreateQuote(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $quote = new QuoteEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($quote);

        $factory = new QuoteFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(QuoteEntity::class, $result);
    }

    public function testWithCustomer(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new QuoteFactory($container);

        $factory->withCustomer('customer-id');

        $this->assertInstanceOf(QuoteFactory::class, $factory);
    }
}
