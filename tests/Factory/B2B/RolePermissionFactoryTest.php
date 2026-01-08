<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\RolePermissionFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Role\RoleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RolePermissionFactoryTest extends TestCase
{
    public function testCreateRole(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $role = new RoleEntity();

        $container->method('get')->willReturnMap([
            ['b2b_components_role.repository', 1, $repository],
            ['b2b_permission.repository', 1, $repository],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($role);
        $searchResult->method('getElements')->willReturn([]);

        $factory = new RolePermissionFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(RoleEntity::class, $result);
    }

    public function testWithPermissions(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new RolePermissionFactory($container);

        $factory->withPermissions(['perm1', 'perm2']);

        $this->assertInstanceOf(RolePermissionFactory::class, $factory);
    }

    public function testCreateAdmin(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $role = new RoleEntity();

        $container->method('get')->willReturnMap([
            ['b2b_components_role.repository', 1, $repository],
            ['b2b_permission.repository', 1, $repository],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($role);
        $searchResult->method('getElements')->willReturn([]);

        $result = RolePermissionFactory::createAdmin($container, Context::createCLIContext());

        $this->assertInstanceOf(RoleEntity::class, $result);
    }
}
