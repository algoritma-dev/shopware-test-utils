<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\EmployeeContextHelper;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmployeeContextHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(EmployeeEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testCreateContextForEmployee(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $employee = new EmployeeEntity();
        $scFactory = $this->createMock(SalesChannelContextFactory::class);
        $context = $this->createMock(SalesChannelContext::class);
        $connection = $this->createMock(Connection::class);

        $employee->setId('employee-id');
        $employee->setBusinessPartnerCustomerId('customer-id');

        $container->method('get')->willReturnMap([
            ['b2b_employee.repository', 1, $repository],
            [SalesChannelContextFactory::class, 1, $scFactory],
            [Connection::class, 1, $connection],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($employee);

        $scFactory->method('create')->willReturn($context);
        $connection->method('fetchOne')->willReturn('sales-channel-id');

        $helper = new EmployeeContextHelper($container);
        $result = $helper->createContextForEmployee('employee-id');

        $this->assertSame($context, $result);
    }

    public function testCreateContextForEmployeeEmail(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $employee = new EmployeeEntity();
        $scFactory = $this->createMock(SalesChannelContextFactory::class);
        $context = $this->createMock(SalesChannelContext::class);
        $connection = $this->createMock(Connection::class);

        $employee->setId('employee-id');
        $employee->setBusinessPartnerCustomerId('customer-id');

        $container->method('get')->willReturnMap([
            ['b2b_employee.repository', 1, $repository],
            [SalesChannelContextFactory::class, 1, $scFactory],
            [Connection::class, 1, $connection],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($employee);

        $scFactory->method('create')->willReturn($context);
        $connection->method('fetchOne')->willReturn('sales-channel-id');

        $helper = new EmployeeContextHelper($container);
        $result = $helper->createContextForEmployeeEmail('test@example.com');

        $this->assertSame($context, $result);
    }
}
