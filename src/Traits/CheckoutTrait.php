<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait CheckoutTrait
{
    use KernelTestBehaviour;

    protected function checkoutPlaceOrder(Cart $cart, SalesChannelContext $context): OrderEntity
    {
        /** @var CartService $cartService */
        $cartService = static::getContainer()->get(CartService::class);

        $orderId = $cartService->order($cart, $context, new RequestDataBag());

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('deliveries');

        $order = static::getContainer()->get('order.repository')->search($criteria, $context->getContext())->first();

        if (! $order instanceof OrderEntity) {
            throw new \RuntimeException(sprintf('Order with ID "%s" not found', $orderId));
        }

        return $order;
    }
}
