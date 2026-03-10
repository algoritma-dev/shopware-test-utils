<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Trait for simulating employee actions on storefront.
 */
trait B2BStorefrontTrait
{
    use KernelTestBehaviour;
    use B2BContextTrait;

    protected function loginEmployeeToStorefront(string $email, string $password): SalesChannelContext
    {
        return $this->loginEmployee($email, $password);
    }

    protected function switchEmployeeOrganization(SalesChannelContext $context, string $organizationId): SalesChannelContext
    {
        return $this->switchOrganizationContext($context, $organizationId);
    }

    protected function canEmployeePerformStorefrontAction(string $employeeId, string $permissionCode, ?Context $context = null): bool
    {
        try {
            $this->assertEmployeeHasPermission($employeeId, $permissionCode, $context);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
