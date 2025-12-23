<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for performing actions on existing carts.
 * Does NOT create carts - use CartFactory for that.
 */
class CartHelper
{
    private readonly CartService $cartService;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->cartService = $this->container->get(CartService::class);
    }

    /**
     * Removes a line item from an existing cart.
     */
    public function removeLineItem(Cart $cart, string $lineItemId, SalesChannelContext $context): Cart
    {
        return $this->cartService->remove($cart, $lineItemId, $context);
    }

    /**
     * Clears all items from an existing cart.
     */
    public function clearCart(Cart $cart, SalesChannelContext $context): Cart
    {
        $lineItems = $cart->getLineItems();

        foreach ($lineItems->getKeys() as $key) {
            $cart = $this->cartService->remove($cart, $key, $context);
        }

        return $cart;
    }

    /**
     * Recalculates an existing cart.
     */
    public function recalculate(Cart $cart, SalesChannelContext $context): Cart
    {
        return $this->cartService->recalculate($cart, $context);
    }

    /**
     * Gets the total price of a cart.
     */
    public function getTotal(Cart $cart): float
    {
        return $cart->getPrice()->getTotalPrice();
    }

    /**
     * Gets the subtotal (without shipping) of a cart.
     */
    public function getSubtotal(Cart $cart): float
    {
        return $cart->getPrice()->getPositionPrice();
    }

    /**
     * Gets the total tax amount of a cart.
     */
    public function getTaxAmount(Cart $cart): float
    {
        return $cart->getPrice()->getCalculatedTaxes()->getAmount();
    }

    /**
     * Updates the quantity for a specific product in the cart.
     */
    public function updateProductQuantity(Cart $cart, string $productId, int $quantity, SalesChannelContext $context): Cart
    {
        $lineItems = $cart->getLineItems();

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                $lineItem->setQuantity($quantity);
                $cart = $this->cartService->recalculate($cart, $context);
                break;
            }
        }

        return $cart;
    }

    /**
     * Checks if a cart contains a specific product.
     */
    public function containsProduct(Cart $cart, string $productId): bool
    {
        $lineItems = $cart->getLineItems();

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the quantity of a specific product in the cart.
     */
    public function getProductQuantity(Cart $cart, string $productId): int
    {
        $lineItems = $cart->getLineItems();

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                return $lineItem->getQuantity();
            }
        }

        return 0;
    }

    /**
     * Gets the line item count in the cart.
     */
    public function getLineItemCount(Cart $cart): int
    {
        return $cart->getLineItems()->count();
    }

    /**
     * Checks if the cart is empty.
     */
    public function isEmpty(Cart $cart): bool
    {
        return $cart->getLineItems()->count() === 0;
    }

    // --- Cart Assertions ---

    /**
     * Assert that the cart contains a specific product.
     */
    public function assertCartContainsProduct(Cart $cart, string $productId): void
    {
        $lineItems = $cart->getLineItems();
        $found = false;

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                $found = true;
                break;
            }
        }

        assert($found, sprintf('Cart does not contain product with ID %s', $productId));
    }

    /**
     * Assert that the cart total matches the expected value.
     */
    public function assertCartTotal(Cart $cart, float $expectedTotal): void
    {
        $actualTotal = $cart->getPrice()->getTotalPrice();
        assert(abs($actualTotal - $expectedTotal) < 0.01, sprintf('Cart total is %.2f, expected %.2f', $actualTotal, $expectedTotal));
    }

    /**
     * Assert that a cart item has the expected quantity.
     */
    public function assertCartItemQuantity(Cart $cart, string $productId, int $expectedQuantity): void
    {
        $lineItems = $cart->getLineItems();
        $quantity = 0;

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                $quantity = $lineItem->getQuantity();
                break;
            }
        }

        assert($quantity === $expectedQuantity, sprintf('Product %s has quantity %d, expected %d', $productId, $quantity, $expectedQuantity));
    }
}
