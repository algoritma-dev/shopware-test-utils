<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Algoritma\ShopwareTestUtils\Helper\CartHelper;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait CartTrait
{
    use KernelTestBehaviour;

    private ?CartHelper $cartHelperInstance = null;

    protected function getCartHelper(): CartHelper
    {
        if (! $this->cartHelperInstance instanceof CartHelper) {
            $this->cartHelperInstance = new CartHelper(static::getContainer());
        }

        return $this->cartHelperInstance;
    }

    protected function cartRemoveLineItem(Cart $cart, string $lineItemId, SalesChannelContext $context): Cart
    {
        return $this->getCartHelper()->removeLineItem($cart, $lineItemId, $context);
    }

    protected function cartClear(Cart $cart, SalesChannelContext $context): Cart
    {
        return $this->getCartHelper()->clearCart($cart, $context);
    }

    protected function cartRecalculate(Cart $cart, SalesChannelContext $context): Cart
    {
        return $this->getCartHelper()->recalculate($cart, $context);
    }

    protected function cartGetTotal(Cart $cart): float
    {
        return $this->getCartHelper()->getTotal($cart);
    }

    protected function cartGetSubtotal(Cart $cart): float
    {
        return $this->getCartHelper()->getSubtotal($cart);
    }

    protected function cartGetTaxAmount(Cart $cart): float
    {
        return $this->getCartHelper()->getTaxAmount($cart);
    }

    protected function cartUpdateProductQuantity(Cart $cart, string $productId, int $quantity, SalesChannelContext $context): Cart
    {
        return $this->getCartHelper()->updateProductQuantity($cart, $productId, $quantity, $context);
    }

    protected function cartContainsProduct(Cart $cart, string $productId): bool
    {
        return $this->getCartHelper()->containsProduct($cart, $productId);
    }

    protected function cartGetProductQuantity(Cart $cart, string $productId): int
    {
        return $this->getCartHelper()->getProductQuantity($cart, $productId);
    }

    protected function cartGetLineItemCount(Cart $cart): int
    {
        return $this->getCartHelper()->getLineItemCount($cart);
    }

    protected function cartIsEmpty(Cart $cart): bool
    {
        return $this->getCartHelper()->isEmpty($cart);
    }

    protected function cartAssertContainsProduct(Cart $cart, string $productId): void
    {
        $this->getCartHelper()->assertCartContainsProduct($cart, $productId);
    }

    protected function cartAssertTotal(Cart $cart, float $expectedTotal): void
    {
        $this->getCartHelper()->assertCartTotal($cart, $expectedTotal);
    }

    protected function cartAssertItemQuantity(Cart $cart, string $productId, int $expectedQuantity): void
    {
        $this->getCartHelper()->assertCartItemQuantity($cart, $productId, $expectedQuantity);
    }
}
