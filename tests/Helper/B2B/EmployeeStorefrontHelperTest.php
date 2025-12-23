<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\EmployeeStorefrontHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Permission\PermissionCollection;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Permission\PermissionEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Role\RoleEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmployeeStorefrontHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(EmployeeEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testCanPerformAction(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $employee = new EmployeeEntity();
        $role = new RoleEntity();

        $role->setPermissions(['test.action']);
        $employee->setRole($role);

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($employee);

        $helper = new EmployeeStorefrontHelper($container);
        $result = $helper->canPerformAction('employee-id', 'test.action');

        $this->assertTrue($result);
    }
}
