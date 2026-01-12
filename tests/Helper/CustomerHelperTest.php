<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\CustomerHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerHelperTest extends TestCase
{
    private CustomerHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CustomerHelper();
    }

    public function testAssertCustomerLoggedInPassesWhenCustomerExists(): void
    {
        $customer = $this->createMock(CustomerEntity::class);
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);

        $this->helper->assertCustomerLoggedIn($context);

        $this->assertTrue(true); // If no exception, assertion passed
    }

    public function testAssertCustomerLoggedInFailsWhenNoCustomer(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn(null);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('No customer logged in');

        $this->helper->assertCustomerLoggedIn($context);
    }

    public function testAssertGuestSessionPassesWhenNoCustomer(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn(null);

        $this->helper->assertGuestSession($context);

        $this->assertTrue(true); // If no exception, assertion passed
    }

    public function testAssertGuestSessionFailsWhenCustomerExists(): void
    {
        $customer = $this->createMock(CustomerEntity::class);
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Customer is logged in but should be guest');

        $this->helper->assertGuestSession($context);
    }

    public function testAssertCustomerHasAddressPassesWhenAddressExists(): void
    {
        $addressId = 'address-123';
        $address = $this->createMock(CustomerAddressEntity::class);
        $address->method('getId')->willReturn($addressId);

        $addresses = new CustomerAddressCollection([$address]);
        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getAddresses')->willReturn($addresses);

        $this->helper->assertCustomerHasAddress($customer, $addressId);

        $this->assertTrue(true); // If no exception, assertion passed
    }

    public function testAssertCustomerHasAddressFailsWhenAddressNotFound(): void
    {
        $address = $this->createMock(CustomerAddressEntity::class);
        $address->method('getId')->willReturn('address-456');

        $addresses = new CustomerAddressCollection([$address]);
        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getAddresses')->willReturn($addresses);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Customer does not have address address-123');

        $this->helper->assertCustomerHasAddress($customer, 'address-123');
    }

    public function testAssertCustomerHasAddressFailsWhenNoAddresses(): void
    {
        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getAddresses')->willReturn(null);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Customer has no addresses');

        $this->helper->assertCustomerHasAddress($customer, 'address-123');
    }

    public function testAssertCustomerBelongsToGroupPassesWhenGroupMatches(): void
    {
        $groupId = 'group-123';
        $group = $this->createMock(CustomerGroupEntity::class);
        $group->method('getId')->willReturn($groupId);

        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getGroup')->willReturn($group);

        $this->helper->assertCustomerBelongsToGroup($customer, $groupId);

        $this->assertTrue(true); // If no exception, assertion passed
    }

    public function testAssertCustomerBelongsToGroupFailsWhenGroupMismatch(): void
    {
        $group = $this->createMock(CustomerGroupEntity::class);
        $group->method('getId')->willReturn('group-456');

        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getGroup')->willReturn($group);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Customer is in group group-456, expected group-123');

        $this->helper->assertCustomerBelongsToGroup($customer, 'group-123');
    }

    public function testAssertCustomerBelongsToGroupFailsWhenNoGroup(): void
    {
        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getGroup')->willReturn(null);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Customer has no group');

        $this->helper->assertCustomerBelongsToGroup($customer, 'group-123');
    }

    public function testAssertCustomerHasRolePassesWhenRoleMatches(): void
    {
        $roleName = 'VIP';
        $group = $this->createMock(CustomerGroupEntity::class);
        $group->method('getName')->willReturn($roleName);

        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getGroup')->willReturn($group);

        $this->helper->assertCustomerHasRole($customer, $roleName);

        $this->assertTrue(true); // If no exception, assertion passed
    }

    public function testAssertCustomerHasRoleFailsWhenRoleMismatch(): void
    {
        $group = $this->createMock(CustomerGroupEntity::class);
        $group->method('getName')->willReturn('Standard');

        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getGroup')->willReturn($group);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Customer is not in group/role VIP');

        $this->helper->assertCustomerHasRole($customer, 'VIP');
    }

    public function testAssertCustomerHasRoleFailsWhenNoGroup(): void
    {
        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getGroup')->willReturn(null);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Customer has no group assigned');

        $this->helper->assertCustomerHasRole($customer, 'VIP');
    }
}
