<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\CheckoutHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CheckoutHelperTest extends TestCase
{
    private MockObject $container;

    private CheckoutHelper $helper;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->helper = new CheckoutHelper($this->container);
    }

    public function testPlaceOrderCreatesOrderAndReturnsEntity(): void
    {
        $cart = new Cart('test-cart');
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $context = $this->createMock(Context::class);
        $salesChannelContext->method('getContext')->willReturn($context);

        $orderId = 'order-id-123';
        $orderEntity = $this->createMock(OrderEntity::class);

        $cartService = $this->createMock(CartService::class);
        $cartService->expects($this->once())
            ->method('order')
            ->with($cart, $salesChannelContext, $this->anything())
            ->willReturn($orderId);

        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('first')->willReturn($orderEntity);

        $repository->expects($this->once())
            ->method('search')
            ->with(
                $this->callback(fn (Criteria $criteria): bool => $criteria->getIds() === [$orderId]),
                $context
            )
            ->willReturn($searchResult);

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [CartService::class, $cartService],
                ['order.repository', $repository],
            ]);

        $result = $this->helper->placeOrder($cart, $salesChannelContext);

        $this->assertSame($orderEntity, $result);
    }

    public function testPlaceOrderLoadsAssociations(): void
    {
        $cart = new Cart('test-cart');
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $context = $this->createMock(Context::class);
        $salesChannelContext->method('getContext')->willReturn($context);

        $orderId = 'order-id-123';
        $orderEntity = $this->createMock(OrderEntity::class);

        $cartService = $this->createMock(CartService::class);
        $cartService->method('order')->willReturn($orderId);

        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('first')->willReturn($orderEntity);

        $repository->expects($this->once())
            ->method('search')
            ->with(
                $this->callback(function (Criteria $criteria): bool {
                    $associations = array_keys($criteria->getAssociations());

                    return in_array('lineItems', $associations)
                        && in_array('transactions', $associations)
                        && in_array('deliveries', $associations);
                }),
                $context
            )
            ->willReturn($searchResult);

        $this->container->method('get')
            ->willReturnMap([
                [CartService::class, $cartService],
                ['order.repository', $repository],
            ]);

        $this->helper->placeOrder($cart, $salesChannelContext);
    }

    public function testPlaceOrderThrowsExceptionWhenOrderNotFound(): void
    {
        $cart = new Cart('test-cart');
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $context = $this->createMock(Context::class);
        $salesChannelContext->method('getContext')->willReturn($context);

        $orderId = 'order-id-123';

        $cartService = $this->createMock(CartService::class);
        $cartService->method('order')->willReturn($orderId);

        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('first')->willReturn(null);

        $repository->method('search')->willReturn($searchResult);

        $this->container->method('get')
            ->willReturnMap([
                [CartService::class, $cartService],
                ['order.repository', $repository],
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Order with ID "order-id-123" not found');

        $this->helper->placeOrder($cart, $salesChannelContext);
    }
}
