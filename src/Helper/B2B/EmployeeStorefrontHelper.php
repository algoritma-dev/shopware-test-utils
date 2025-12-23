<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for simulating employee actions on storefront.
 * Pure helper: orchestrates storefront actions, uses EmployeeLoginHelper and other helpers.
 */
class EmployeeStorefrontHelper
{
    private readonly EmployeeLoginHelper $loginHelper;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->loginHelper = new EmployeeLoginHelper($this->container);
    }

    /**
     * Simulate employee login.
     */
    public function login(string $email, string $password): SalesChannelContext
    {
        return $this->loginHelper->login($email, $password);
    }

    /**
     * Simulate employee switching organization.
     */
    public function switchOrganization(SalesChannelContext $context, string $organizationId): SalesChannelContext
    {
        $helper = new OrganizationContextHelper($this->container);

        return $helper->switchOrganization($context, $organizationId);
    }

    /**
     * Check if employee can perform action.
     */
    public function canPerformAction(string $employeeId, string $permissionCode, ?Context $context = null): bool
    {
        $context ??= Context::createDefaultContext();
        $repository = $this->container->get('b2b_employee.repository');

        $criteria = new Criteria([$employeeId]);
        $criteria->addAssociation('role.permissions');

        $employee = $repository->search($criteria, $context)->first();

        if (! $employee || ! $employee->getRole()) {
            return false;
        }

        $permissions = $employee->getRole()->getPermissions();
        if (! $permissions) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($permission->getCode() === $permissionCode) {
                return true;
            }
        }

        return false;
    }
}
