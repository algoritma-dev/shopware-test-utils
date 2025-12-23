<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\OrganizationFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\OrganizationUnit\Entity\OrganizationEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrganizationFactoryTest extends TestCase
{
    public function testCreateOrganization(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $organization = new OrganizationEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($organization);

        $factory = new OrganizationFactory($container);
        $result = $factory->create(Context::createDefaultContext());

        $this->assertInstanceOf(OrganizationEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new OrganizationFactory($container);

        $factory->withName('Test Org');

        $this->assertInstanceOf(OrganizationFactory::class, $factory);
    }
}
