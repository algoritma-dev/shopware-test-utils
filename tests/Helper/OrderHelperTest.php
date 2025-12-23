<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\OrderHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderHelperTest extends TestCase
{
    public function testPlaceOrder(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $cartService = $this->createMock(CartService::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $order = new OrderEntity();
        $cart = new Cart('token', 'token');
        $context = $this->createMock(SalesChannelContext::class);

        $container->method('get')->willReturnMap([
            [CartService::class, 1, $cartService],
            ['order.repository', 1, $repository],
        ]);

        $cartService->method('recalculate')->willReturn($cart);
        $cartService->method('order')->willReturn('order-id');

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($order);

        $helper = new OrderHelper($container);
        $result = $helper->placeOrder($cart, $context);

        $this->assertSame($order, $result);
    }

    public function testGetOrder(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $order = new OrderEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($order);

        $helper = new OrderHelper($container);
        $result = $helper->getOrder('order-id');

        $this->assertSame($order, $result);
    }
}
