<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteRequestHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuoteRequestHelperTest extends TestCase
{
    public function testRequestQuote(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $cart = $this->createMock(Cart::class);
        $context = $this->createMock(SalesChannelContext::class);
        $customer = $this->createMock(CustomerEntity::class);
        $currency = $this->createMock(CurrencyEntity::class);
        $price = $this->createMock(CartPrice::class);
        $lineItem = $this->createMock(LineItem::class);

        $container->method('get')->willReturn($repository);
        $repository->expects($this->once())->method('create');

        $context->method('getCustomer')->willReturn($customer);
        $customer->method('getId')->willReturn('customer-id');
        $context->method('getSalesChannelId')->willReturn('sales-channel-id');
        $context->method('getCurrency')->willReturn($currency);
        $currency->method('getId')->willReturn('currency-id');
        $context->method('getContext')->willReturn(Context::createCLIContext());

        $cart->method('getPrice')->willReturn($price);
        $cart->method('getLineItems')->willReturn(new LineItemCollection([$lineItem]));
        $lineItem->method('getReferencedId')->willReturn('product-id');
        $lineItem->method('getQuantity')->willReturn(1);

        $helper = new QuoteRequestHelper($container);
        $result = $helper->requestQuote($cart, $context);

        $this->assertIsString($result);
    }
}
