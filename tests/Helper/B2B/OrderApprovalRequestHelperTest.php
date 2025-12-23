<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\OrderApprovalRequestHelper;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderApprovalRequestHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(PendingOrderEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testRequestApproval(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $pendingOrder = new PendingOrderEntity();
        $cart = $this->createMock(Cart::class);
        $context = $this->createMock(SalesChannelContext::class);
        $customer = $this->createMock(CustomerEntity::class);
        $currency = $this->createMock(CurrencyEntity::class);
        $price = $this->createMock(CartPrice::class);
        $connection = $this->createMock(Connection::class);
        $cartService = $this->createMock(CartService::class);

        $pendingOrder->setId('pending-id');
        $customer->setId('customer-id');
        $currency->setId('currency-id');
        $cart->method('getPrice')->willReturn($price);

        $container->method('get')->willReturnMap([
            ['b2b_components_pending_order.repository', 1, $repository],
            [Connection::class, 1, $connection],
            [CartService::class, 1, $cartService],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($pendingOrder);
        $connection->method('fetchOne')->willReturn('state-id');

        $context->method('getCustomer')->willReturn($customer);
        $context->method('getCurrency')->willReturn($currency);
        $context->method('getSalesChannelId')->willReturn('sales-channel-id');
        $context->method('getContext')->willReturn(Context::createDefaultContext());

        $helper = new OrderApprovalRequestHelper($container);
        $result = $helper->requestApproval($cart, $context, 'employee-id');

        $this->assertEquals('pending-id', $result);
    }
}
