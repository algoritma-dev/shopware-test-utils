<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait CartTrait
{
    use KernelTestBehaviour;

    protected function cartRemoveLineItem(Cart $cart, string $lineItemId, SalesChannelContext $context): Cart
    {
        return static::getContainer()->get(CartService::class)->remove($cart, $lineItemId, $context);
    }

    protected function cartClear(Cart $cart, SalesChannelContext $context): Cart
    {
        $cartService = static::getContainer()->get(CartService::class);
        $lineItems = $cart->getLineItems();

        foreach ($lineItems->getKeys() as $key) {
            $cart = $cartService->remove($cart, $key, $context);
        }

        return $cart;
    }

    protected function cartRecalculate(Cart $cart, SalesChannelContext $context): Cart
    {
        return static::getContainer()->get(CartService::class)->recalculate($cart, $context);
    }

    protected function cartGetTotal(Cart $cart): float
    {
        return $cart->getPrice()->getTotalPrice();
    }

    protected function cartGetSubtotal(Cart $cart): float
    {
        return $cart->getPrice()->getPositionPrice();
    }

    protected function cartGetTaxAmount(Cart $cart): float
    {
        return $cart->getPrice()->getCalculatedTaxes()->getAmount();
    }

    protected function cartUpdateProductQuantity(Cart $cart, string $productId, int $quantity, SalesChannelContext $context): Cart
    {
        $lineItems = $cart->getLineItems();
        $cartService = static::getContainer()->get(CartService::class);

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                $lineItem->setQuantity($quantity);
                $cart = $cartService->recalculate($cart, $context);
                break;
            }
        }

        return $cart;
    }

    protected function cartContainsProduct(Cart $cart, string $productId): bool
    {
        $lineItems = $cart->getLineItems();

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                return true;
            }
        }

        return false;
    }

    protected function cartGetProductQuantity(Cart $cart, string $productId): int
    {
        $lineItems = $cart->getLineItems();

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                return $lineItem->getQuantity();
            }
        }

        return 0;
    }

    protected function cartGetLineItemCount(Cart $cart): int
    {
        return $cart->getLineItems()->count();
    }

    protected function cartIsEmpty(Cart $cart): bool
    {
        return $cart->getLineItems()->count() === 0;
    }

    protected function cartAssertContainsProduct(Cart $cart, string $productId): void
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

    protected function cartAssertTotal(Cart $cart, float $expectedTotal): void
    {
        $actualTotal = $cart->getPrice()->getTotalPrice();
        assert(abs($actualTotal - $expectedTotal) < 0.01, sprintf('Cart total is %.2f, expected %.2f', $actualTotal, $expectedTotal));
    }

    protected function cartAssertItemQuantity(Cart $cart, string $productId, int $expectedQuantity): void
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
