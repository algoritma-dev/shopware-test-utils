<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for simulating quote requests from storefront.
 * Converts carts to quote requests in functional tests.
 */
class QuoteRequestHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Convert cart to quote request.
     */
    public function requestQuote(Cart $cart, SalesChannelContext $context, array $additionalData = []): string
    {
        $quoteId = Uuid::randomHex();

        $data = array_merge([
            'id' => $quoteId,
            'customerId' => $context->getCustomer()?->getId(),
            'salesChannelId' => $context->getSalesChannelId(),
            'currencyId' => $context->getCurrency()->getId(),
            'price' => $cart->getPrice(),
            'lineItems' => $this->convertCartLineItems($cart),
        ], $additionalData);

        $repository = $this->container->get('quote.repository');
        $repository->create([$data], $context->getContext());

        return $quoteId;
    }

    /**
     * Request quote with comment.
     */
    public function requestQuoteWithComment(Cart $cart, SalesChannelContext $context, string $comment): string
    {
        $quoteId = $this->requestQuote($cart, $context);

        $commentHelper = new QuoteCommentHelper($this->container);
        $commentHelper->addComment($quoteId, $comment, null, $context->getContext());

        return $quoteId;
    }

    private function convertCartLineItems(Cart $cart): array
    {
        $lineItems = [];

        foreach ($cart->getLineItems() as $lineItem) {
            $lineItems[] = [
                'id' => Uuid::randomHex(),
                'productId' => $lineItem->getReferencedId(),
                'quantity' => $lineItem->getQuantity(),
                'price' => $lineItem->getPrice(),
            ];
        }

        return $lineItems;
    }
}
