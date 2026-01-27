<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for performing actions on orders.
 * Handles order operations like placement, cancellation, state transitions, etc.
 */
class OrderHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Places an order from a cart.
     * This is an ACTION on a cart that produces an order.
     */
    public function placeOrder(Cart $cart, SalesChannelContext $context): OrderEntity
    {
        /** @var CartService $cartService */
        $cartService = $this->container->get(CartService::class);

        // Ensure cart is recalculated and valid
        $cart = $cartService->recalculate($cart, $context);

        // Place the order
        $orderId = $cartService->order($cart, $context, new RequestDataBag());

        // Fetch and return the created OrderEntity
        return $this->getOrder($orderId, $context->getContext());
    }

    /**
     * Gets an order by ID with associations.
     */
    public function getOrder(string $orderId, ?Context $context = null): ?OrderEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<OrderCollection> $orderRepository */
        $orderRepository = $this->container->get('order.repository');

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('stateMachineState');

        $entity = $orderRepository->search($criteria, $context)->first();

        return $entity instanceof OrderEntity ? $entity : null;
    }

    /**
     * Cancels an order.
     */
    public function cancelOrder(string $orderId, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        $stateManager = new StateManager($this->container);
        $stateManager->transitionOrderState($orderId, 'cancel', $context);
    }

    /**
     * Marks an order as paid.
     */
    public function markOrderAsPaid(string $orderId, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        $order = $this->getOrder($orderId, $context);
        $transaction = $order->getTransactions()->first();

        if ($transaction) {
            $stateManager = new StateManager($this->container);
            $stateManager->transitionPaymentState($transaction->getId(), 'pay', $context);
        }
    }

    /**
     * Marks an order as shipped.
     */
    public function markOrderAsShipped(string $orderId, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        $order = $this->getOrder($orderId, $context);
        $delivery = $order->getDeliveries()->first();

        if ($delivery) {
            $stateManager = new StateManager($this->container);
            $stateManager->transitionDeliveryState($delivery->getId(), 'ship', $context);
        }
    }

    /**
     * Gets the total amount of an order.
     */
    public function getOrderTotal(OrderEntity $order): float
    {
        return $order->getPrice()->getTotalPrice();
    }

    /**
     * Checks if an order has a specific line item.
     */
    public function hasLineItem(OrderEntity $order, string $productId): bool
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

    // --- Order Assertions ---

    /**
     * Assert that an order is in a specific state.
     */
    public function assertOrderState(OrderEntity $order, string $expectedState): void
    {
        $stateName = $order->getStateMachineState()?->getTechnicalName();
        assert($stateName === $expectedState, sprintf('Order state is "%s", expected "%s"', $stateName, $expectedState));
    }

    /**
     * Assert that an order has at least one transaction.
     */
    public function assertOrderHasTransaction(OrderEntity $order): void
    {
        $transactions = $order->getTransactions();
        assert($transactions instanceof OrderTransactionCollection, 'Order has no transactions collection');
        assert($transactions->count() > 0, 'Order has no transactions');
    }

    /**
     * Assert that an order has at least one delivery.
     */
    public function assertOrderHasDelivery(OrderEntity $order): void
    {
        $deliveries = $order->getDeliveries();
        assert($deliveries instanceof OrderDeliveryCollection, 'Order has no deliveries collection');
        assert($deliveries->count() > 0, 'Order has no deliveries');
    }

    /**
     * Assert that a line item has the expected price.
     */
    public function assertLineItemPrice(OrderEntity $order, string $lineItemId, float $expectedPrice): void
    {
        $lineItems = $order->getLineItems();
        assert($lineItems instanceof OrderLineItemCollection, 'Order has no line items');

        $lineItem = $lineItems->get($lineItemId);
        assert($lineItem !== null, sprintf('Line item %s not found in order', $lineItemId));

        $actualPrice = $lineItem->getPrice()->getTotalPrice();
        assert(abs($actualPrice - $expectedPrice) < 0.01, sprintf('Line item %s has price %.2f, expected %.2f', $lineItemId, $actualPrice, $expectedPrice));
    }
}
