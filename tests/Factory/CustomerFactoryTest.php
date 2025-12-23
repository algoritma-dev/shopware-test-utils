<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\CustomerFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomerFactoryTest extends TestCase
{
    public function testCreateCustomer(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $idSearchResult = $this->createStub(IdSearchResult::class);
        $customer = new CustomerEntity();
        $connection = $this->createStub(Connection::class);

        $container->method('get')->willReturnMap([
            ['customer.repository', 1, $repository],
            ['salutation.repository', 1, $repository],
            ['country.repository', 1, $repository],
            ['payment_method.repository', 1, $repository],
            [Connection::class, 1, $connection],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($customer);

        $repository->method('searchIds')->willReturn($idSearchResult);
        $idSearchResult->method('firstId')->willReturn('some-id');

        $connection->method('fetchOne')->willReturn('sales-channel-id');

        $factory = new CustomerFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(CustomerEntity::class, $result);
    }

    public function testWithEmail(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $idSearchResult = $this->createStub(IdSearchResult::class);

        $container->method('get')->willReturn($repository);
        $repository->method('searchIds')->willReturn($idSearchResult);
        $idSearchResult->method('firstId')->willReturn('some-id');

        $factory = new CustomerFactory($container);
        $factory->withEmail('test@example.com');

        $this->assertInstanceOf(CustomerFactory::class, $factory);
    }
}
