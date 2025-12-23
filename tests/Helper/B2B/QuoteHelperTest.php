<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\QuoteManagement\Domain\QuoteToCart\QuoteToCartConverter;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuoteHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(QuoteEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testConvertQuoteToCart(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $converter = $this->createMock(QuoteToCartConverter::class);
        $quote = new QuoteEntity();
        $context = $this->createMock(SalesChannelContext::class);
        $cart = new Cart('token', 'token');

        $container->method('get')->willReturn($converter);
        $converter->method('convertToCart')->willReturn($cart);

        $helper = new QuoteHelper($container);
        $result = $helper->convertQuoteToCart($quote, $context);

        $this->assertSame($cart, $result);
    }
}
