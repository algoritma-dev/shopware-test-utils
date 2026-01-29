<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\EmployeeContextHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\EmployeeLoginHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\OrganizationContextHelper;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\OrganizationUnit\Entity\OrganizationEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait B2BContextTrait
{
    use KernelTestBehaviour;

    private ?EmployeeContextHelper $b2bEmployeeContextHelperInstance = null;

    private ?EmployeeLoginHelper $b2bEmployeeLoginHelperInstance = null;

    private ?OrganizationContextHelper $b2bOrganizationContextHelperInstance = null;

    protected function getB2bEmployeeContextHelper(): EmployeeContextHelper
    {
        if (! $this->b2bEmployeeContextHelperInstance instanceof EmployeeContextHelper) {
            $this->b2bEmployeeContextHelperInstance = new EmployeeContextHelper(static::getContainer());
        }

        return $this->b2bEmployeeContextHelperInstance;
    }

    protected function getB2bEmployeeLoginHelper(): EmployeeLoginHelper
    {
        if (! $this->b2bEmployeeLoginHelperInstance instanceof EmployeeLoginHelper) {
            $this->b2bEmployeeLoginHelperInstance = new EmployeeLoginHelper(static::getContainer());
        }

        return $this->b2bEmployeeLoginHelperInstance;
    }

    protected function getB2bOrganizationContextHelper(): OrganizationContextHelper
    {
        if (! $this->b2bOrganizationContextHelperInstance instanceof OrganizationContextHelper) {
            $this->b2bOrganizationContextHelperInstance = new OrganizationContextHelper(static::getContainer());
        }

        return $this->b2bOrganizationContextHelperInstance;
    }

    protected function b2bEmployeeContextCreateForEmployee(string $employeeId, ?string $salesChannelId = null): SalesChannelContext
    {
        return $this->getB2bEmployeeContextHelper()->createContextForEmployee($employeeId, $salesChannelId);
    }

    protected function b2bEmployeeContextCreateForEmployeeEmail(string $email, ?string $salesChannelId = null): SalesChannelContext
    {
        return $this->getB2bEmployeeContextHelper()->createContextForEmployeeEmail($email, $salesChannelId);
    }

    protected function b2bEmployeeContextCreateFromEmployee(EmployeeEntity $employee, ?string $salesChannelId = null): SalesChannelContext
    {
        return $this->getB2bEmployeeContextHelper()->createContextFromEmployee($employee, $salesChannelId);
    }

    protected function b2bEmployeeAssertHasPermission(string $employeeId, string $permissionCode, ?Context $context = null): void
    {
        $this->getB2bEmployeeContextHelper()->assertEmployeeHasPermission($employeeId, $permissionCode, $context);
    }

    protected function b2bEmployeeAssertHasRole(string $employeeId, string $roleId, ?Context $context = null): void
    {
        $this->getB2bEmployeeContextHelper()->assertEmployeeHasRole($employeeId, $roleId, $context);
    }

    protected function b2bEmployeeLogin(string $email, string $password, ?string $salesChannelId = null): SalesChannelContext
    {
        return $this->getB2bEmployeeLoginHelper()->login($email, $password, $salesChannelId);
    }

    protected function b2bEmployeeLoginByEmail(string $email, ?string $salesChannelId = null): SalesChannelContext
    {
        return $this->getB2bEmployeeLoginHelper()->loginByEmail($email, $salesChannelId);
    }

    protected function b2bEmployeeLoginById(string $employeeId, ?string $salesChannelId = null): SalesChannelContext
    {
        return $this->getB2bEmployeeLoginHelper()->loginById($employeeId, $salesChannelId);
    }

    protected function b2bOrganizationCreateContext(
        string $organizationId,
        ?string $employeeId = null,
        ?string $salesChannelId = null
    ): SalesChannelContext {
        return $this->getB2bOrganizationContextHelper()->createForOrganization(
            $organizationId,
            $employeeId,
            $salesChannelId
        );
    }

    protected function b2bOrganizationCreateContextForCustomerDefault(
        string $customerId,
        ?string $salesChannelId = null
    ): SalesChannelContext {
        return $this->getB2bOrganizationContextHelper()->createForCustomerDefaultOrganization(
            $customerId,
            $salesChannelId
        );
    }

    protected function b2bOrganizationSwitch(SalesChannelContext $currentContext, string $newOrganizationId): SalesChannelContext
    {
        return $this->getB2bOrganizationContextHelper()->switchOrganization($currentContext, $newOrganizationId);
    }

    /**
     * @return array<OrganizationEntity>
     */
    protected function b2bOrganizationGetCustomerOrganizations(string $customerId): array
    {
        return $this->getB2bOrganizationContextHelper()->getCustomerOrganizations($customerId);
    }

    protected function b2bOrganizationEmployeeBelongs(string $employeeId, string $organizationId): bool
    {
        return $this->getB2bOrganizationContextHelper()->employeeBelongsToOrganization($employeeId, $organizationId);
    }
}
