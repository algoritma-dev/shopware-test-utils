<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\B2BContextFactory;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Role\RoleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for creating authenticated employee contexts.
 * Pure helper: orchestrates employee context creation, delegates to factory.
 */
class EmployeeContextHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Create an authenticated context for an employee by ID.
     */
    public function createContextForEmployee(string $employeeId, ?string $salesChannelId = null): SalesChannelContext
    {
        $employee = $this->loadEmployee($employeeId);

        if (! $employee instanceof EmployeeEntity) {
            throw new \RuntimeException(sprintf('Employee with ID "%s" not found', $employeeId));
        }

        return $this->createContextFromEmployee($employee, $salesChannelId);
    }

    /**
     * Create an authenticated context for an employee by email.
     */
    public function createContextForEmployeeEmail(string $email, ?string $salesChannelId = null): SalesChannelContext
    {
        $employee = $this->loadEmployeeByEmail($email);

        if (! $employee instanceof EmployeeEntity) {
            throw new \RuntimeException(sprintf('Employee with email "%s" not found', $email));
        }

        return $this->createContextFromEmployee($employee, $salesChannelId);
    }

    /**
     * Create context from an EmployeeEntity.
     */
    public function createContextFromEmployee(EmployeeEntity $employee, ?string $salesChannelId = null): SalesChannelContext
    {
        $factory = new B2BContextFactory($this->container);

        $factory->withEmployee($employee->getId());

        if ($employee->getBusinessPartnerCustomerId() !== '' && $employee->getBusinessPartnerCustomerId() !== '0') {
            $factory->withCustomer($employee->getBusinessPartnerCustomerId());
        }

        if ($employee->getRoleId()) {
            $factory->withRole($employee->getRoleId());
        }

        if (\method_exists($employee, 'getOrganizationId') && $employee->getOrganizationId()) {
            $factory->withOrganization($employee->getOrganizationId());
        }

        if ($salesChannelId) {
            $factory->withSalesChannel($salesChannelId);
        }

        return $factory->create();
    }

    // --- Employee Assertions ---

    /**
     * Assert employee has a specific permission.
     */
    public function assertEmployeeHasPermission(string $employeeId, string $permissionCode, ?Context $context = null): void
    {
        $employee = $this->loadEmployeeWithAssociations($employeeId, $context);
        $role = $employee->getRole();

        if (! $role instanceof RoleEntity) {
            throw new \RuntimeException(sprintf('Employee "%s" has no role assigned', $employeeId));
        }

        $permissions = $role->getPermissions();
        if (! $permissions) {
            throw new \RuntimeException(sprintf('Role "%s" has no permissions', $role->getId()));
        }

        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($permission === $permissionCode) {
                $hasPermission = true;
                break;
            }
        }

        assert(
            $hasPermission,
            sprintf('Expected employee to have permission "%s", but it was not found', $permissionCode)
        );
    }

    /**
     * Assert employee has role.
     */
    public function assertEmployeeHasRole(string $employeeId, string $roleId, ?Context $context = null): void
    {
        $employee = $this->loadEmployeeWithAssociations($employeeId, $context);

        assert(
            $employee->getRoleId() === $roleId,
            sprintf('Expected employee to have role "%s", but has "%s"', $roleId, $employee->getRoleId())
        );
    }

    private function loadEmployee(string $employeeId): ?EmployeeEntity
    {
        /** @var EntityRepository<EmployeeEntity> $repository */
        $repository = $this->container->get('b2b_employee.repository');

        $criteria = new Criteria([$employeeId]);
        $criteria->addAssociation('businessPartnerCustomer');
        $criteria->addAssociation('role');
        $criteria->addAssociation('organization');

        /** @var EmployeeEntity|null $entity */
        $entity = $repository->search($criteria, Context::createCLIContext())->first();

        return $entity;
    }

    private function loadEmployeeByEmail(string $email): ?EmployeeEntity
    {
        /** @var EntityRepository<EmployeeEntity> $repository */
        $repository = $this->container->get('b2b_employee.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $criteria->addAssociation('businessPartnerCustomer');
        $criteria->addAssociation('role');
        $criteria->addAssociation('organization');

        /** @var EmployeeEntity|null $entity */
        $entity = $repository->search($criteria, Context::createCLIContext())->first();

        return $entity;
    }

    private function loadEmployeeWithAssociations(string $employeeId, ?Context $context): EmployeeEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository<EmployeeEntity> $repository */
        $repository = $this->container->get('b2b_employee.repository');
        $criteria = new Criteria([$employeeId]);
        $criteria->addAssociation('role');
        $criteria->addAssociation('role.permissions');

        $employee = $repository->search($criteria, $context)->first();
        if (! $employee) {
            throw new \RuntimeException(sprintf('Employee with ID "%s" not found', $employeeId));
        }

        return $employee;
    }
}
