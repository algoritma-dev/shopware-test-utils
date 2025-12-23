<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
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

        /** @var EntityRepository $orderRepository */
        $orderRepository = $this->container->get('order.repository');

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('stateMachineState');

        return $orderRepository->search($criteria, $context)->first();
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
}
