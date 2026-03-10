<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;
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
        Assert::assertInstanceOf(CustomerEntity::class, $customer, 'No customer logged in (context has no customer)');
    }

    protected function customerAssertGuestSession(SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();
        Assert::assertNotInstanceOf(CustomerEntity::class, $customer, 'Customer is logged in but should be guest');
    }

    protected function customerAssertHasAddress(CustomerEntity $customer, string $addressId): void
    {
        $addresses = $customer->getAddresses();
        Assert::assertInstanceOf(CustomerAddressCollection::class, $addresses, 'Customer has no addresses');

        $found = false;
        foreach ($addresses as $address) {
            if ($address->getId() === $addressId) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue($found, sprintf('Customer does not have address %s', $addressId));
    }

    protected function customerAssertBelongsToGroup(CustomerEntity $customer, string $groupId): void
    {
        $group = $customer->getGroup();
        Assert::assertInstanceOf(CustomerGroupEntity::class, $group, 'Customer has no group');
        Assert::assertSame($groupId, $group->getId(), sprintf('Customer is in group %s, expected %s', $group->getId(), $groupId));
    }

    protected function customerAssertHasRole(CustomerEntity $customer, string $role): void
    {
        // Implementation depends on how roles are stored/checked in the specific project context.
        // Assuming a simple check for now or placeholder.
        // For standard Shopware, checking customer group name.

        $group = $customer->getGroup();
        Assert::assertInstanceOf(CustomerGroupEntity::class, $group, 'Customer has no group assigned.');
        Assert::assertSame($role, $group->getName(), sprintf('Customer is not in group/role %s', $role));
    }
}
