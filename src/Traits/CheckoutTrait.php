<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Algoritma\ShopwareTestUtils\Helper\CheckoutHelper;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait CheckoutTrait
{
    use KernelTestBehaviour;

    private ?CheckoutHelper $checkoutHelperInstance = null;

    protected function getCheckoutHelper(): CheckoutHelper
    {
        if (! $this->checkoutHelperInstance instanceof CheckoutHelper) {
            $this->checkoutHelperInstance = new CheckoutHelper(static::getContainer());
        }

        return $this->checkoutHelperInstance;
    }

    protected function checkoutPlaceOrder(Cart $cart, SalesChannelContext $context): OrderEntity
    {
        return $this->getCheckoutHelper()->placeOrder($cart, $context);
    }
}
