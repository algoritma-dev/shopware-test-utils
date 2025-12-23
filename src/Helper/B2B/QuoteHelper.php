<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\QuoteManagement\Domain\QuoteToCart\QuoteToCartConverter;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteStates;
use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteLineItem\QuoteLineItemCollection;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
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

    // --- Quote Assertions ---

    /**
     * Assert quote is in a specific state.
     */
    public function assertQuoteInState(string $quoteId, string $expectedState, ?Context $context = null): void
    {
        $quote = $this->loadQuoteEntity($quoteId, $context);
        $actualState = $quote->getStateMachineState()->getTechnicalName();

        assert(
            $actualState === $expectedState,
            sprintf('Expected quote to be in state "%s", but got "%s"', $expectedState, $actualState)
        );
    }

    /**
     * Assert quote has comments.
     */
    public function assertQuoteHasComments(string $quoteId, ?int $expectedCount = null, ?Context $context = null): void
    {
        $comments = $this->loadQuoteComments($quoteId, $context);

        if ($expectedCount !== null) {
            assert(
                count($comments) === $expectedCount,
                sprintf('Expected quote to have %d comments, but has %d', $expectedCount, count($comments))
            );
        } else {
            assert(
                count($comments) > 0,
                sprintf('Expected quote "%s" to have comments, but none found', $quoteId)
            );
        }
    }

    /**
     * Assert quote can be converted to order.
     */
    public function assertQuoteCanBeConverted(string $quoteId, ?Context $context = null): void
    {
        $quote = $this->loadQuoteEntity($quoteId, $context);

        assert($quote->getLineItems() instanceof QuoteLineItemCollection, 'Quote has no line items');
        assert(count($quote->getLineItems()) > 0, 'Quote has empty line items');

        $state = $quote->getStateMachineState()->getTechnicalName();
        assert(
            $state === QuoteStates::STATE_ACCEPTED,
            sprintf('Quote must be in accepted state to be converted, but is in state "%s"', $state)
        );
    }

    private function loadQuoteEntity(string $quoteId, ?Context $context): QuoteEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository $repository */
        $repository = $this->container->get('quote.repository');
        $criteria = new Criteria([$quoteId]);
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('lineItems');

        $quote = $repository->search($criteria, $context)->first();
        if (! $quote) {
            throw new \RuntimeException(sprintf('Quote with ID "%s" not found', $quoteId));
        }

        return $quote;
    }

    private function loadQuoteComments(string $quoteId, ?Context $context): array
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository $repository */
        $repository = $this->container->get('quote_comment.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('quoteId', $quoteId));

        return array_values($repository->search($criteria, $context)->getElements());
    }
}
