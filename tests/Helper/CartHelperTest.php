<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\CartHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CartHelperTest extends TestCase
{
    public function testRemoveLineItem(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $cartService = $this->createMock(CartService::class);
        $cart = new Cart('token', 'token');
        $context = $this->createMock(SalesChannelContext::class);

        $container->method('get')->willReturn($cartService);
        $cartService->method('remove')->willReturn($cart);

        $helper = new CartHelper($container);
        $result = $helper->removeLineItem($cart, 'item-id', $context);

        $this->assertSame($cart, $result);
    }

    public function testRecalculate(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $cartService = $this->createMock(CartService::class);
        $cart = new Cart('token', 'token');
        $context = $this->createMock(SalesChannelContext::class);

        $container->method('get')->willReturn($cartService);
        $cartService->method('recalculate')->willReturn($cart);

        $helper = new CartHelper($container);
        $result = $helper->recalculate($cart, $context);

        $this->assertSame($cart, $result);
    }
}
