<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Helper for customer-related operations and assertions.
 */
class CustomerHelper
{
    // --- Customer Assertions ---

    /**
     * Assert that a customer is logged in.
     */
    public function assertCustomerLoggedIn(SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();
        assert($customer instanceof CustomerEntity, 'No customer logged in (context has no customer)');
    }

    /**
     * Assert that the session is a guest session.
     */
    public function assertGuestSession(SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();
        assert(! $customer instanceof CustomerEntity, 'Customer is logged in but should be guest');
    }

    /**
     * Assert that a customer has a specific address.
     */
    public function assertCustomerHasAddress(CustomerEntity $customer, string $addressId): void
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

    /**
     * Assert that a customer belongs to a specific group.
     */
    public function assertCustomerBelongsToGroup(CustomerEntity $customer, string $groupId): void
    {
        $group = $customer->getGroup();
        assert($group instanceof CustomerGroupEntity, 'Customer has no group');
        assert($group->getId() === $groupId, sprintf('Customer is in group %s, expected %s', $group->getId(), $groupId));
    }

    /**
     * Assert that a customer has a specific role.
     */
    public function assertCustomerHasRole(CustomerEntity $customer, string $role): void
    {
        // Implementation depends on how roles are stored/checked in the specific project context.
        // Assuming a simple check for now or placeholder.
        // For standard Shopware, checking customer group name.

        $group = $customer->getGroup();
        assert($group instanceof CustomerGroupEntity, 'Customer has no group assigned.');
        assert($group->getName() === $role, sprintf('Customer is not in group/role %s', $role));
    }
}
