<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Algoritma\ShopwareTestUtils\Helper\StateManager;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait OrderTrait
{
    use KernelTestBehaviour;

    protected function orderPlace(Cart $cart, SalesChannelContext $context): OrderEntity
    {
        $cartService = static::getContainer()->get(CartService::class);

        // Ensure cart is recalculated and valid
        $cart = $cartService->recalculate($cart, $context);

        // Place the order
        $orderId = $cartService->order($cart, $context, new RequestDataBag());

        // Fetch and return the created OrderEntity
        return $this->orderGet($orderId, $context->getContext());
    }

    protected function orderGet(string $orderId, ?Context $context = null): ?OrderEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('stateMachineState');

        $entity = static::getContainer()->get('order.repository')->search($criteria, $context)->first();

        return $entity instanceof OrderEntity ? $entity : null;
    }

    protected function orderCancel(string $orderId, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        $stateManager = new StateManager(static::getContainer());
        $stateManager->transitionOrderState($orderId, 'cancel', $context);
    }

    protected function orderMarkAsPaid(string $orderId, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        $order = $this->orderGet($orderId, $context);
        $transaction = $order->getTransactions()->first();

        if ($transaction) {
            $stateManager = new StateManager(static::getContainer());
            $stateManager->transitionPaymentState($transaction->getId(), 'pay', $context);
        }
    }

    protected function orderMarkAsShipped(string $orderId, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        $order = $this->orderGet($orderId, $context);
        $delivery = $order->getDeliveries()->first();

        if ($delivery) {
            $stateManager = new StateManager(static::getContainer());
            $stateManager->transitionDeliveryState($delivery->getId(), 'ship', $context);
        }
    }

    protected function orderGetTotal(OrderEntity $order): float
    {
        return $order->getPrice()->getTotalPrice();
    }

    protected function orderHasLineItem(OrderEntity $order, string $productId): bool
    {
        $lineItems = $order->getLineItems();

        if (! $lineItems instanceof OrderLineItemCollection) {
            return false;
        }

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                return true;
            }
        }

        return false;
    }

    protected function orderAssertState(OrderEntity $order, string $expectedState): void
    {
        $stateName = $order->getStateMachineState()?->getTechnicalName();
        assert($stateName === $expectedState, sprintf('Order state is "%s", expected "%s"', $stateName, $expectedState));
    }

    protected function orderAssertHasTransaction(OrderEntity $order): void
    {
        $transactions = $order->getTransactions();
        assert($transactions instanceof OrderTransactionCollection, 'Order has no transactions collection');
        assert($transactions->count() > 0, 'Order has no transactions');
    }

    protected function orderAssertHasDelivery(OrderEntity $order): void
    {
        $deliveries = $order->getDeliveries();
        assert($deliveries instanceof OrderDeliveryCollection, 'Order has no deliveries collection');
        assert($deliveries->count() > 0, 'Order has no deliveries');
    }

    protected function orderAssertLineItemPrice(OrderEntity $order, string $lineItemId, float $expectedPrice): void
    {
        $lineItems = $order->getLineItems();
        assert($lineItems instanceof OrderLineItemCollection, 'Order has no line items');

        $lineItem = $lineItems->get($lineItemId);
        assert($lineItem !== null, sprintf('Line item %s not found in order', $lineItemId));

        $actualPrice = $lineItem->getPrice()->getTotalPrice();
        assert(abs($actualPrice - $expectedPrice) < 0.01, sprintf('Line item %s has price %.2f, expected %.2f', $lineItemId, $actualPrice, $expectedPrice));
    }
}
