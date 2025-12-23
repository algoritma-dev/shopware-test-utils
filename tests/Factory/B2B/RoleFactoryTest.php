<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\RoleFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Role\RoleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RoleFactoryTest extends TestCase
{
    public function testCreateRole(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $role = new RoleEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($role);

        $factory = new RoleFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(RoleEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new RoleFactory($container);

        $factory->withName('Test Role');

        $this->assertInstanceOf(RoleFactory::class, $factory);
    }
}
