<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Helper\CustomerHelper;
use Algoritma\ShopwareTestUtils\Helper\OrderHelper;
use Algoritma\ShopwareTestUtils\Traits\CustomerTrait;
use Algoritma\ShopwareTestUtils\Traits\OrderTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderCustomerTraitTest extends TestCase
{
    public function testOrderTraitDelegatesToHelper(): void
    {
        $orderHelper = $this->createMock(OrderHelper::class);
        $cart = $this->createStub(Cart::class);
        $salesChannelContext = $this->createStub(SalesChannelContext::class);
        $order = $this->createStub(OrderEntity::class);

        $orderHelper->expects($this->once())
            ->method('placeOrder')
            ->with($cart, $salesChannelContext)
            ->willReturn($order);

        $orderHelper->expects($this->once())
            ->method('cancelOrder')
            ->with('order-id', null);

        $orderHelper->expects($this->once())
            ->method('assertOrderState')
            ->with($order, 'open');

        $subject = new class($orderHelper) {
            use OrderTrait;

            public function __construct(private OrderHelper $orderHelper) {}

            protected function getOrderHelper(): OrderHelper
            {
                return $this->orderHelper;
            }

            public function callOrderPlace(Cart $cart, SalesChannelContext $context): OrderEntity
            {
                return $this->orderPlace($cart, $context);
            }

            public function callOrderCancel(string $orderId): void
            {
                $this->orderCancel($orderId);
            }

            public function callOrderAssertState(OrderEntity $order, string $expectedState): void
            {
                $this->orderAssertState($order, $expectedState);
            }
        };

        $this->assertSame($order, $subject->callOrderPlace($cart, $salesChannelContext));
        $subject->callOrderCancel('order-id');
        $subject->callOrderAssertState($order, 'open');
    }

    public function testCustomerTraitDelegatesToHelper(): void
    {
        $customerHelper = $this->createMock(CustomerHelper::class);
        $context = $this->createStub(SalesChannelContext::class);
        $customer = $this->createStub(CustomerEntity::class);

        $customerHelper->expects($this->once())
            ->method('assertCustomerLoggedIn')
            ->with($context);

        $customerHelper->expects($this->once())
            ->method('assertCustomerHasAddress')
            ->with($customer, 'address-id');

        $subject = new class($customerHelper) {
            use CustomerTrait;

            public function __construct(private CustomerHelper $customerHelper) {}

            protected function getCustomerHelper(): CustomerHelper
            {
                return $this->customerHelper;
            }

            public function callCustomerAssertLoggedIn(SalesChannelContext $context): void
            {
                $this->customerAssertLoggedIn($context);
            }

            public function callCustomerAssertHasAddress(CustomerEntity $customer, string $addressId): void
            {
                $this->customerAssertHasAddress($customer, $addressId);
            }
        };

        $subject->callCustomerAssertLoggedIn($context);
        $subject->callCustomerAssertHasAddress($customer, 'address-id');
    }
}
