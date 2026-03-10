<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait CustomerTrait
{
    use KernelTestBehaviour;

    protected function customerAssertLoggedIn(SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();
        assert($customer instanceof CustomerEntity, 'No customer logged in (context has no customer)');
    }

    protected function customerAssertGuestSession(SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();
        assert(! $customer instanceof CustomerEntity, 'Customer is logged in but should be guest');
    }

    protected function customerAssertHasAddress(CustomerEntity $customer, string $addressId): void
    {
        $addresses = $customer->getAddresses();
        assert($addresses instanceof CustomerAddressCollection, 'Customer has no addresses');

        $found = false;
        foreach ($addresses as $address) {
            if ($address->getId() === $addressId) {
                $found = true;
                break;
            }
        }

        assert($found, sprintf('Customer does not have address %s', $addressId));
    }

    protected function customerAssertBelongsToGroup(CustomerEntity $customer, string $groupId): void
    {
        $group = $customer->getGroup();
        assert($group instanceof CustomerGroupEntity, 'Customer has no group');
        assert($group->getId() === $groupId, sprintf('Customer is in group %s, expected %s', $group->getId(), $groupId));
    }

    protected function customerAssertHasRole(CustomerEntity $customer, string $role): void
    {
        // Implementation depends on how roles are stored/checked in the specific project context.
        // Assuming a simple check for now or placeholder.
        // For standard Shopware, checking customer group name.

        $group = $customer->getGroup();
        assert($group instanceof CustomerGroupEntity, 'Customer has no group assigned.');
        assert($group->getName() === $role, sprintf('Customer is not in group/role %s', $role));
    }
}
