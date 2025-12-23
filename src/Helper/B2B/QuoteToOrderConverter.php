<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\CheckoutRunner;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteStates;
use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteLineItem\QuoteLineItemCollection;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts accepted quotes to orders for testing.
 * Handles quote line items → cart → order conversion.
 */
class QuoteToOrderConverter
{
    private readonly CartService $cartService;

    private readonly CheckoutRunner $checkoutRunner;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->cartService = $this->container->get(CartService::class);
        $this->checkoutRunner = new CheckoutRunner($this->container);
    }

    /**
     * Convert an accepted quote to an order.
     */
    public function convertToOrder(string $quoteId, SalesChannelContext $context): OrderEntity
    {
        $quote = $this->loadQuote($quoteId, $context->getContext());

        // Create cart from quote
        $cart = $this->createCartFromQuote($quote, $context);

        // Place order
        return $this->checkoutRunner->placeOrder($cart, $context);
    }

    /**
     * Create a cart from quote line items.
     */
    public function createCartFromQuote(QuoteEntity $quote, SalesChannelContext $context): Cart
    {
        $cart = $this->cartService->createNew($context->getToken());

        if (! $quote->getLineItems() instanceof QuoteLineItemCollection) {
            throw new \RuntimeException('Quote has no line items');
        }

        foreach ($quote->getLineItems() as $quoteLineItem) {
            $lineItem = new LineItem(
                $quoteLineItem->getId(),
                LineItem::PRODUCT_LINE_ITEM_TYPE,
                $quoteLineItem->getProductId(),
                $quoteLineItem->getQuantity()
            );

            // Set price from quote
            if ($quoteLineItem->getPrice() instanceof CalculatedPrice) {
                $lineItem->setPriceDefinition(
                    new QuantityPriceDefinition(
                        $quoteLineItem->getPrice()->getUnitPrice(),
                        $quoteLineItem->getPrice()->getTaxRules(),
                        $quoteLineItem->getQuantity()
                    )
                );
            }

            $cart->add($lineItem);
        }

        return $this->cartService->recalculate($cart, $context);
    }

    /**
     * Simulate quote acceptance and order placement.
     */
    public function acceptAndConvertToOrder(
        string $quoteId,
        SalesChannelContext $context,
        ?QuoteStateMachineHelper $stateMachineHelper = null
    ): OrderEntity {
        $stateMachineHelper ??= new QuoteStateMachineHelper($this->container);

        // Ensure quote is accepted
        $stateMachineHelper->acceptQuote($quoteId, $context->getContext());

        // Convert to order
        return $this->convertToOrder($quoteId, $context);
    }

    /**
     * Get quote total amount.
     */
    public function getQuoteTotal(string $quoteId, ?Context $context = null): float
    {
        $quote = $this->loadQuote($quoteId, $context);

        if (! $quote->getPrice()) {
            return 0.0;
        }

        return $quote->getPrice()->getTotalPrice();
    }

    /**
     * Validate quote can be converted to order.
     */
    public function canConvertToOrder(string $quoteId, ?Context $context = null): bool
    {
        $quote = $this->loadQuote($quoteId, $context);

        // Check if quote is in accepted state
        $state = $quote->getStateMachineState()->getTechnicalName();
        if ($state !== QuoteStates::STATE_ACCEPTED) {
            return false;
        }

        // Check if quote has line items
        return $quote->getLineItems() instanceof QuoteLineItemCollection && count($quote->getLineItems()) !== 0;
    }

    private function loadQuote(string $quoteId, ?Context $context): QuoteEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<QuoteEntity> $repository */
        $repository = $this->container->get('quote.repository');

        $criteria = new Criteria([$quoteId]);
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');
        $criteria->addAssociation('customer');
        $criteria->addAssociation('price');

        /** @var QuoteEntity|null $quote */
        $quote = $repository->search($criteria, $context)->first();

        if (! $quote) {
            throw new \RuntimeException(sprintf('Quote with ID "%s" not found', $quoteId));
        }

        return $quote;
    }
}
