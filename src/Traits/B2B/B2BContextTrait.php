<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\B2BContextFactory;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeCollection;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Role\RoleEntity;
use Shopware\Commercial\B2B\OrganizationUnit\Entity\OrganizationEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Trait for managing B2B employee and organization contexts.
 */
trait B2BContextTrait
{
    use KernelTestBehaviour;

    protected function createContextForEmployee(string $employeeId, ?string $salesChannelId = null): SalesChannelContext
    {
        $employee = $this->getEmployeeById($employeeId);

        return $this->createContextFromEmployeeEntity($employee, $salesChannelId);
    }

    protected function createContextForEmployeeEmail(string $email, ?string $salesChannelId = null): SalesChannelContext
    {
        $employee = $this->getEmployeeByEmail($email);

        return $this->createContextFromEmployeeEntity($employee, $salesChannelId);
    }

    protected function createContextFromEmployeeEntity(EmployeeEntity $employee, ?string $salesChannelId = null): SalesChannelContext
    {
        $factory = new B2BContextFactory(static::getContainer());

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

    protected function assertEmployeeHasPermission(string $employeeId, string $permissionCode, ?Context $context = null): void
    {
        $employee = $this->getEmployeeWithRole($employeeId, $context);
        $role = $employee->getRole();

        if (! $role instanceof RoleEntity) {
            throw new \RuntimeException(sprintf('Employee "%s" has no role assigned', $employeeId));
        }

        $permissions = $role->getPermissions();
        if (! $permissions) {
            throw new \RuntimeException(sprintf('Role "%s" has no permissions', $role->getId()));
        }

        assert(
            in_array($permissionCode, $permissions, true),
            sprintf('Expected employee to have permission "%s", but it was not found', $permissionCode)
        );
    }

    protected function assertEmployeeHasRole(string $employeeId, string $roleId, ?Context $context = null): void
    {
        $employee = $this->getEmployeeWithRole($employeeId, $context);

        assert(
            $employee->getRoleId() === $roleId,
            sprintf('Expected employee to have role "%s", but has "%s"', $roleId, $employee->getRoleId())
        );
    }

    protected function loginEmployee(string $email, string $password, ?string $salesChannelId = null): SalesChannelContext
    {
        // In testing, we skip password verification
        return $this->createContextForEmployeeEmail($email, $salesChannelId);
    }

    protected function loginEmployeeByEmail(string $email, ?string $salesChannelId = null): SalesChannelContext
    {
        return $this->loginEmployee($email, '', $salesChannelId);
    }

    protected function loginEmployeeById(string $employeeId, ?string $salesChannelId = null): SalesChannelContext
    {
        return $this->createContextForEmployee($employeeId, $salesChannelId);
    }

    protected function createContextForOrganization(
        string $organizationId,
        ?string $employeeId = null,
        ?string $salesChannelId = null
    ): SalesChannelContext {
        $organization = $this->getOrganizationById($organizationId);

        $factory = new B2BContextFactory(static::getContainer());
        $factory->withOrganization($organizationId);

        if ($organization->customerId) {
            $factory->withCustomer($organization->customerId);
        }

        if ($employeeId) {
            $factory->withEmployee($employeeId);
        }

        if ($salesChannelId) {
            $factory->withSalesChannel($salesChannelId);
        }

        return $factory->create();
    }

    protected function createContextForCustomerDefaultOrganization(
        string $customerId,
        ?string $salesChannelId = null
    ): SalesChannelContext {
        $organizations = $this->getCustomerOrganizations($customerId);

        if ($organizations === []) {
            throw new \RuntimeException(sprintf('No default organization found for customer "%s"', $customerId));
        }

        return $this->createContextForOrganization($organizations[0]->id, null, $salesChannelId);
    }

    protected function switchOrganizationContext(
        SalesChannelContext $currentContext,
        string $newOrganizationId
    ): SalesChannelContext {
        $this->getOrganizationById($newOrganizationId);

        $factory = new B2BContextFactory(static::getContainer());
        $factory->withOrganization($newOrganizationId);
        $factory->withSalesChannel($currentContext->getSalesChannelId());

        /** @var EmployeeEntity|null $employee */
        $employee = $currentContext->getCustomer()?->getExtension('employee');
        if ($employee) {
            $factory->withEmployee($employee->getId());
        }

        if ($currentContext->getCustomer() instanceof CustomerEntity) {
            $factory->withCustomer($currentContext->getCustomer()->getId());
        }

        return $factory->create();
    }

    /**
     * @return array<OrganizationEntity>
     */
    protected function getCustomerOrganizations(string $customerId): array
    {
        $repository = $this->getOrganizationRepository();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addAssociation('employees');
        $criteria->addAssociation('paymentMethods');
        $criteria->addAssociation('shippingMethods');

        /** @var array<string, OrganizationEntity> $elements */
        $elements = $repository->search($criteria, Context::createCLIContext())->getElements();

        return array_values($elements);
    }

    protected function isEmployeeInOrganization(string $employeeId, string $organizationId): bool
    {
        $employee = $this->getEmployeeById($employeeId);

        if (! method_exists($employee, 'getOrganizationId')) {
            return false;
        }

        return $employee->getOrganizationId() === $organizationId;
    }

    private function getEmployeeById(string $employeeId): EmployeeEntity
    {
        /** @var EntityRepository<EmployeeCollection> $repository */
        $repository = static::getContainer()->get('b2b_employee.repository');

        $criteria = new Criteria([$employeeId]);
        $criteria->addAssociation('businessPartnerCustomer');
        $criteria->addAssociation('role');
        $criteria->addAssociation('organization');

        $entity = $repository->search($criteria, Context::createCLIContext())->first();

        if (! $entity instanceof EmployeeEntity) {
            throw new \RuntimeException(sprintf('Employee with ID "%s" not found', $employeeId));
        }

        return $entity;
    }

    private function getEmployeeByEmail(string $email): EmployeeEntity
    {
        /** @var EntityRepository<EmployeeCollection> $repository */
        $repository = static::getContainer()->get('b2b_employee.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $criteria->addAssociation('businessPartnerCustomer');
        $criteria->addAssociation('role');
        $criteria->addAssociation('organization');

        $entity = $repository->search($criteria, Context::createCLIContext())->first();

        if (! $entity instanceof EmployeeEntity) {
            throw new \RuntimeException(sprintf('Employee with email "%s" not found', $email));
        }

        return $entity;
    }

    private function getEmployeeWithRole(string $employeeId, ?Context $context = null): EmployeeEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository<EmployeeCollection> $repository */
        $repository = static::getContainer()->get('b2b_employee.repository');

        $criteria = new Criteria([$employeeId]);
        $criteria->addAssociation('role');
        $criteria->addAssociation('role.permissions');

        $employee = $repository->search($criteria, $context)->first();
        if (! $employee instanceof EmployeeEntity) {
            throw new \RuntimeException(sprintf('Employee with ID "%s" not found', $employeeId));
        }

        return $employee;
    }

    private function getOrganizationById(string $organizationId): OrganizationEntity
    {
        $repository = $this->getOrganizationRepository();

        $criteria = new Criteria([$organizationId]);
        $criteria->addAssociation('customer');
        $criteria->addAssociation('employees');
        $criteria->addAssociation('paymentMethods');
        $criteria->addAssociation('shippingMethods');
        $criteria->addAssociation('defaultShippingAddress');
        $criteria->addAssociation('defaultBillingAddress');

        $entity = $repository->search($criteria, Context::createCLIContext())->first();

        if (! $entity instanceof OrganizationEntity) {
            throw new \RuntimeException(sprintf('Organization with ID "%s" not found', $organizationId));
        }

        return $entity;
    }

    /**
     * @return EntityRepository<EntityCollection<OrganizationEntity>>
     */
    private function getOrganizationRepository(): EntityRepository
    {
        return static::getContainer()->get('b2b_components_organization.repository');
    }
}
