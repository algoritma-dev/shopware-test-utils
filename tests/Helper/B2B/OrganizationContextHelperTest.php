<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\OrganizationContextHelper;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\OrganizationUnit\Entity\OrganizationEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrganizationContextHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(OrganizationEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testCreateForOrganization(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $organization = new OrganizationEntity();
        $scFactory = $this->createMock(SalesChannelContextFactory::class);
        $context = $this->createMock(SalesChannelContext::class);
        $connection = $this->createMock(Connection::class);

        $organization->setId('org-id');

        $container->method('get')->willReturnMap([
            ['b2b_components_organization.repository', 1, $repository],
            [SalesChannelContextFactory::class, 1, $scFactory],
            [Connection::class, 1, $connection],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($organization);

        $scFactory->method('create')->willReturn($context);
        $connection->method('fetchOne')->willReturn('sales-channel-id');

        $helper = new OrganizationContextHelper($container);
        $result = $helper->createForOrganization('org-id');

        $this->assertSame($context, $result);
    }

    public function testGetCustomerOrganizations(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $organization = new OrganizationEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('getElements')->willReturn([$organization]);

        $helper = new OrganizationContextHelper($container);
        $result = $helper->getCustomerOrganizations('customer-id');

        $this->assertCount(1, $result);
        $this->assertSame($organization, $result[0]);
    }
}
