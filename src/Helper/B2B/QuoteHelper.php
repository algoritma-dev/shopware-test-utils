<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\QuoteManagement\Domain\QuoteToCart\QuoteToCartConverter;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuoteHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    public function convertQuoteToCart(QuoteEntity $quote, SalesChannelContext $context): Cart
    {
        /** @var QuoteToCartConverter $converter */
        $converter = $this->container->get(QuoteToCartConverter::class);

        return $converter->convertToCart($quote, $context);
    }
}
