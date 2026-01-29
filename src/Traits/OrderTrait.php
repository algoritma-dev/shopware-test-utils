<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Algoritma\ShopwareTestUtils\Helper\OrderHelper;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait OrderTrait
{
    use KernelTestBehaviour;

    private ?OrderHelper $orderHelperInstance = null;

    protected function getOrderHelper(): OrderHelper
    {
        if (! $this->orderHelperInstance instanceof OrderHelper) {
            $this->orderHelperInstance = new OrderHelper(static::getContainer());
        }

        return $this->orderHelperInstance;
    }

    protected function orderPlace(Cart $cart, SalesChannelContext $context): OrderEntity
    {
        return $this->getOrderHelper()->placeOrder($cart, $context);
    }

    protected function orderGet(string $orderId, ?Context $context = null): ?OrderEntity
    {
        return $this->getOrderHelper()->getOrder($orderId, $context);
    }

    protected function orderCancel(string $orderId, ?Context $context = null): void
    {
        $this->getOrderHelper()->cancelOrder($orderId, $context);
    }

    protected function orderMarkAsPaid(string $orderId, ?Context $context = null): void
    {
        $this->getOrderHelper()->markOrderAsPaid($orderId, $context);
    }

    protected function orderMarkAsShipped(string $orderId, ?Context $context = null): void
    {
        $this->getOrderHelper()->markOrderAsShipped($orderId, $context);
    }

    protected function orderGetTotal(OrderEntity $order): float
    {
        return $this->getOrderHelper()->getOrderTotal($order);
    }

    protected function orderHasLineItem(OrderEntity $order, string $productId): bool
    {
        return $this->getOrderHelper()->hasLineItem($order, $productId);
    }

    protected function orderAssertState(OrderEntity $order, string $expectedState): void
    {
        $this->getOrderHelper()->assertOrderState($order, $expectedState);
    }

    protected function orderAssertHasTransaction(OrderEntity $order): void
    {
        $this->getOrderHelper()->assertOrderHasTransaction($order);
    }

    protected function orderAssertHasDelivery(OrderEntity $order): void
    {
        $this->getOrderHelper()->assertOrderHasDelivery($order);
    }

    protected function orderAssertLineItemPrice(OrderEntity $order, string $lineItemId, float $expectedPrice): void
    {
        $this->getOrderHelper()->assertLineItemPrice($order, $lineItemId, $expectedPrice);
    }
}
