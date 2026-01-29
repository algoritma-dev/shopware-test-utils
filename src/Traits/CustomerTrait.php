<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Algoritma\ShopwareTestUtils\Helper\CustomerHelper;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait CustomerTrait
{
    use KernelTestBehaviour;

    private ?CustomerHelper $customerHelperInstance = null;

    protected function getCustomerHelper(): CustomerHelper
    {
        if (! $this->customerHelperInstance instanceof CustomerHelper) {
            $this->customerHelperInstance = new CustomerHelper();
        }

        return $this->customerHelperInstance;
    }

    protected function customerAssertLoggedIn(SalesChannelContext $context): void
    {
        $this->getCustomerHelper()->assertCustomerLoggedIn($context);
    }

    protected function customerAssertGuestSession(SalesChannelContext $context): void
    {
        $this->getCustomerHelper()->assertGuestSession($context);
    }

    protected function customerAssertHasAddress(CustomerEntity $customer, string $addressId): void
    {
        $this->getCustomerHelper()->assertCustomerHasAddress($customer, $addressId);
    }

    protected function customerAssertBelongsToGroup(CustomerEntity $customer, string $groupId): void
    {
        $this->getCustomerHelper()->assertCustomerBelongsToGroup($customer, $groupId);
    }

    protected function customerAssertHasRole(CustomerEntity $customer, string $role): void
    {
        $this->getCustomerHelper()->assertCustomerHasRole($customer, $role);
    }
}
