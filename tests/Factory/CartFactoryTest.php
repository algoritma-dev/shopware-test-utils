<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\CartFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CartFactoryTest extends TestCase
{
    public function testCreateCart(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $context = $this->createStub(SalesChannelContext::class);
        $cartService = $this->createStub(CartService::class);
        $cart = new Cart('test-token', 'test-token');

        $container->method('get')->willReturn($cartService);
        $cartService->method('getCart')->willReturn($cart);

        $factory = new CartFactory($container, $context);
        $result = $factory->create();

        $this->assertSame($cart, $result);
    }

    public function testWithProduct(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $context = $this->createStub(SalesChannelContext::class);
        $cartService = $this->createStub(CartService::class);
        $cart = new Cart('test-token', 'test-token');

        $container->method('get')->willReturn($cartService);
        $cartService->method('getCart')->willReturn($cart);
        $cartService->method('add')->willReturn($cart);

        $factory = new CartFactory($container, $context);
        $factory->withProduct('product-id');

        $this->assertInstanceOf(CartFactory::class, $factory);
    }
}
