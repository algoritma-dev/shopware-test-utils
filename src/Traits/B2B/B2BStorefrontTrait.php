<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\EmployeeStorefrontHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait B2BStorefrontTrait
{
    use KernelTestBehaviour;

    private ?EmployeeStorefrontHelper $b2bEmployeeStorefrontHelperInstance = null;

    protected function getB2bEmployeeStorefrontHelper(): EmployeeStorefrontHelper
    {
        if (! $this->b2bEmployeeStorefrontHelperInstance instanceof EmployeeStorefrontHelper) {
            $this->b2bEmployeeStorefrontHelperInstance = new EmployeeStorefrontHelper(static::getContainer());
        }

        return $this->b2bEmployeeStorefrontHelperInstance;
    }

    protected function b2bStorefrontLogin(string $email, string $password): SalesChannelContext
    {
        return $this->getB2bEmployeeStorefrontHelper()->login($email, $password);
    }

    protected function b2bStorefrontSwitchOrganization(SalesChannelContext $context, string $organizationId): SalesChannelContext
    {
        return $this->getB2bEmployeeStorefrontHelper()->switchOrganization($context, $organizationId);
    }

    protected function b2bStorefrontCanPerformAction(string $employeeId, string $permissionCode, ?Context $context = null): bool
    {
        return $this->getB2bEmployeeStorefrontHelper()->canPerformAction($employeeId, $permissionCode, $context);
    }
}
