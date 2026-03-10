<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Shopware\Commercial\B2B\QuoteManagement\Domain\QuoteToCart\QuoteToCartConverter;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteCollection;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteStates;
use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteComment\QuoteCommentCollection;
use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteComment\QuoteCommentEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteDocument\QuoteDocumentCollection;
use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteDocument\QuoteDocumentEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteLineItem\QuoteLineItemCollection;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

/**
 * Trait for B2B quote management operations and assertions.
 */
trait B2BQuoteTrait
{
    use KernelTestBehaviour;

    protected function convertQuoteToCart(QuoteEntity $quote, SalesChannelContext $context): Cart
    {
        /** @var QuoteToCartConverter $converter */
        $converter = static::getContainer()->get(QuoteToCartConverter::class);

        return $converter->convertToCart($quote, $context);
    }

    protected function assertQuoteInState(string $quoteId, string $expectedState, ?Context $context = null): void
    {
        $quote = $this->getQuoteEntityById($quoteId, $context);
        $actualState = $quote->getStateMachineState()->getTechnicalName();

        assert(
            $actualState === $expectedState,
            sprintf('Expected quote to be in state "%s", but got "%s"', $expectedState, $actualState)
        );
    }

    protected function assertQuoteHasComments(string $quoteId, ?int $expectedCount = null, ?Context $context = null): void
    {
        $comments = $this->getQuoteComments($quoteId, $context);

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

    protected function assertQuoteCanBeConverted(string $quoteId, ?Context $context = null): void
    {
        $quote = $this->getQuoteEntityById($quoteId, $context);

        assert($quote->getLineItems() instanceof QuoteLineItemCollection, 'Quote has no line items');
        assert(count($quote->getLineItems()) > 0, 'Quote has empty line items');

        $state = $quote->getStateMachineState()->getTechnicalName();
        assert(
            $state === QuoteStates::STATE_ACCEPTED,
            sprintf('Quote must be in accepted state to be converted, but is in state "%s"', $state)
        );
    }

    protected function transitionQuote(string $quoteId, string $action, ?Context $context = null): QuoteEntity
    {
        $context ??= Context::createCLIContext();

        $transition = new Transition(
            'quote',
            $quoteId,
            $action,
            'stateId'
        );

        $this->getQuoteStateMachineRegistry()->transition($transition, $context);

        return $this->getQuoteEntityById($quoteId, $context);
    }

    protected function requestQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transitionQuote($quoteId, QuoteStates::ACTION_PROCESS, $context);
    }

    protected function sendQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transitionQuote($quoteId, QuoteStates::ACTION_SENT, $context);
    }

    protected function acceptQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transitionQuote($quoteId, QuoteStates::ACTION_ACCEPT, $context);
    }

    protected function declineQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transitionQuote($quoteId, QuoteStates::ACTION_DECLINE, $context);
    }

    protected function expireQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transitionQuote($quoteId, QuoteStates::ACTION_EXPIRE, $context);
    }

    protected function requestQuoteChanges(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transitionQuote($quoteId, QuoteStates::ACTION_REQUEST_CHANGE, $context);
    }

    protected function reopenQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transitionQuote($quoteId, QuoteStates::ACTION_REOPEN, $context);
    }

    protected function getQuoteState(string $quoteId, ?Context $context = null): string
    {
        $quote = $this->getQuoteEntityById($quoteId, $context);

        return $quote->getStateMachineState()->getTechnicalName();
    }

    protected function isQuoteInState(string $quoteId, string $state, ?Context $context = null): bool
    {
        return $this->getQuoteState($quoteId, $context) === $state;
    }

    /**
     * @return array<int, StateMachineTransitionEntity>
     */
    protected function getAvailableQuoteTransitions(string $quoteId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        return $this->getQuoteStateMachineRegistry()->getAvailableTransitions('quote', $quoteId, 'stateId', $context);
    }

    protected function simulateFullQuoteAcceptanceWorkflow(string $quoteId, ?Context $context = null): QuoteEntity
    {
        $this->requestQuote($quoteId, $context);
        $this->sendQuote($quoteId, $context);

        return $this->acceptQuote($quoteId, $context);
    }

    protected function simulateFullQuoteDeclineWorkflow(string $quoteId, ?Context $context = null): QuoteEntity
    {
        $this->requestQuote($quoteId, $context);
        $this->sendQuote($quoteId, $context);

        return $this->declineQuote($quoteId, $context);
    }

    protected function simulateQuoteNegotiationWorkflow(string $quoteId, ?Context $context = null): QuoteEntity
    {
        $this->requestQuote($quoteId, $context);
        $this->sendQuote($quoteId, $context);
        $this->requestQuoteChanges($quoteId, $context);
        $this->sendQuote($quoteId, $context);

        return $this->acceptQuote($quoteId, $context);
    }

    protected function addQuoteComment(
        string $quoteId,
        string $message,
        ?string $employeeId = null,
        ?Context $context = null
    ): string {
        $context ??= Context::createCLIContext();

        $repository = $this->getQuoteCommentRepository();
        $commentId = Uuid::randomHex();

        $data = [
            'id' => $commentId,
            'quoteId' => $quoteId,
            'message' => $message,
        ];

        if ($employeeId) {
            $data['employeeId'] = $employeeId;
        }

        $repository->create([$data], $context);

        return $commentId;
    }

    /**
     * @param array<string> $messages
     *
     * @return array<string>
     */
    protected function addQuoteComments(string $quoteId, array $messages, ?string $employeeId = null, ?Context $context = null): array
    {
        $commentIds = [];

        foreach ($messages as $message) {
            $commentIds[] = $this->addQuoteComment($quoteId, $message, $employeeId, $context);
        }

        return $commentIds;
    }

    /**
     * @return array<QuoteCommentEntity>
     */
    protected function getQuoteComments(string $quoteId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        $repository = $this->getQuoteCommentRepository();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('quoteId', $quoteId));
        $criteria->addAssociation('employee');
        $criteria->addSorting(new FieldSorting('createdAt', 'ASC'));

        /** @var array<string, QuoteCommentEntity> $elements */
        $elements = $repository->search($criteria, $context)->getElements();

        return array_values($elements);
    }

    protected function getQuoteCommentCount(string $quoteId, ?Context $context = null): int
    {
        return count($this->getQuoteComments($quoteId, $context));
    }

    protected function getLatestQuoteComment(string $quoteId, ?Context $context = null): ?QuoteCommentEntity
    {
        $comments = $this->getQuoteComments($quoteId, $context);

        return $comments === [] ? null : end($comments);
    }

    protected function deleteQuoteComment(string $commentId, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $this->getQuoteCommentRepository()->delete([['id' => $commentId]], $context);
    }

    protected function deleteAllQuoteComments(string $quoteId, ?Context $context = null): void
    {
        $comments = $this->getQuoteComments($quoteId, $context);

        if ($comments === []) {
            return;
        }

        $context ??= Context::createCLIContext();
        $ids = array_map(fn (QuoteCommentEntity $comment): array => ['id' => $comment->getId()], $comments);
        $this->getQuoteCommentRepository()->delete($ids, $context);
    }

    /**
     * @param array<int, array<string, string>> $conversation
     *
     * @return array<string>
     */
    protected function simulateQuoteConversation(
        string $quoteId,
        array $conversation,
        ?Context $context = null
    ): array {
        $commentIds = [];

        foreach ($conversation as $entry) {
            $message = $entry['message'];
            $employeeId = $entry['employeeId'] ?? null;

            $commentIds[] = $this->addQuoteComment($quoteId, $message, $employeeId, $context);
        }

        return $commentIds;
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function generateQuoteDocument(
        string $quoteId,
        string $documentType = 'quote',
        array $config = [],
        ?Context $context = null
    ): string {
        $context ??= Context::createCLIContext();

        $repository = $this->getQuoteDocumentRepository();
        $documentId = Uuid::randomHex();

        $data = [
            'id' => $documentId,
            'quoteId' => $quoteId,
            'documentType' => $documentType,
            'config' => $config,
        ];

        $repository->create([$data], $context);

        return $documentId;
    }

    /**
     * @return array<QuoteDocumentEntity>
     */
    protected function getQuoteDocuments(string $quoteId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        $repository = $this->getQuoteDocumentRepository();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('quoteId', $quoteId));
        $criteria->addAssociation('quote');

        /** @var array<string, QuoteDocumentEntity> $elements */
        $elements = $repository->search($criteria, $context)->getElements();

        return array_values($elements);
    }

    protected function getQuoteDocument(string $documentId, ?Context $context = null): ?QuoteDocumentEntity
    {
        $context ??= Context::createCLIContext();

        $repository = $this->getQuoteDocumentRepository();
        $criteria = new Criteria([$documentId]);
        $criteria->addAssociation('quote');

        $entity = $repository->search($criteria, $context)->first();

        return $entity instanceof QuoteDocumentEntity ? $entity : null;
    }

    protected function deleteQuoteDocument(string $documentId, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $this->getQuoteDocumentRepository()->delete([['id' => $documentId]], $context);
    }

    protected function hasQuoteDocuments(string $quoteId, ?Context $context = null): bool
    {
        return count($this->getQuoteDocuments($quoteId, $context)) > 0;
    }

    protected function getQuoteDocumentCount(string $quoteId, ?Context $context = null): int
    {
        return count($this->getQuoteDocuments($quoteId, $context));
    }

    protected function getLatestQuoteDocument(string $quoteId, ?Context $context = null): ?QuoteDocumentEntity
    {
        $documents = $this->getQuoteDocuments($quoteId, $context);

        if ($documents === []) {
            return null;
        }

        // Sort by created date descending
        usort($documents, fn (QuoteDocumentEntity $a, QuoteDocumentEntity $b): int => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $documents[0];
    }

    /**
     * @return array<string>
     */
    protected function regenerateQuoteDocuments(string $quoteId, ?Context $context = null): array
    {
        // Delete existing documents
        $existingDocuments = $this->getQuoteDocuments($quoteId, $context);
        foreach ($existingDocuments as $doc) {
            $this->deleteQuoteDocument($doc->getId(), $context);
        }

        // Generate new document
        $documentId = $this->generateQuoteDocument($quoteId, 'quote', [], $context);

        return [$documentId];
    }

    /**
     * @param array<string, mixed> $additionalData
     */
    protected function requestQuoteFromCart(
        Cart $cart,
        SalesChannelContext $context,
        array $additionalData = []
    ): string {
        $quoteId = Uuid::randomHex();

        $data = array_merge([
            'id' => $quoteId,
            'customerId' => $context->getCustomer()?->getId(),
            'salesChannelId' => $context->getSalesChannelId(),
            'currencyId' => $context->getCurrency()->getId(),
            'price' => $cart->getPrice(),
            'lineItems' => $this->convertCartToQuoteLineItems($cart),
        ], $additionalData);

        $repository = $this->getQuoteRepository();
        $repository->create([$data], $context->getContext());

        return $quoteId;
    }

    protected function requestQuoteFromCartWithComment(Cart $cart, SalesChannelContext $context, string $comment): string
    {
        $quoteId = $this->requestQuoteFromCart($cart, $context);
        $this->addQuoteComment($quoteId, $comment, null, $context->getContext());

        return $quoteId;
    }

    protected function convertQuoteToOrder(string $quoteId, SalesChannelContext $context): OrderEntity
    {
        $quote = $this->getQuoteEntityById($quoteId, $context->getContext());

        // Create cart from quote
        $cart = $this->createCartFromQuote($quote, $context);

        // Place order
        $orderId = $this->getB2bCartService()->order($cart, $context, new RequestDataBag());

        return $this->getB2bOrderById($orderId, $context->getContext());
    }

    protected function createCartFromQuote(QuoteEntity $quote, SalesChannelContext $context): Cart
    {
        $cart = $this->getB2bCartService()->createNew($context->getToken());

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

        return $this->getB2bCartService()->recalculate($cart, $context);
    }

    protected function acceptQuoteAndConvertToOrder(
        string $quoteId,
        SalesChannelContext $context
    ): OrderEntity {
        // Ensure quote is accepted
        $this->acceptQuote($quoteId, $context->getContext());

        // Convert to order
        return $this->convertQuoteToOrder($quoteId, $context);
    }

    protected function getQuoteTotalAmount(string $quoteId, ?Context $context = null): float
    {
        $quote = $this->getQuoteEntityById($quoteId, $context);

        return $quote->getPrice()->getTotalPrice();
    }

    protected function canQuoteBeConvertedToOrder(string $quoteId, ?Context $context = null): bool
    {
        $quote = $this->getQuoteEntityById($quoteId, $context);

        // Check if quote is in accepted state
        $state = $quote->getStateMachineState()->getTechnicalName();
        if ($state !== QuoteStates::STATE_ACCEPTED) {
            return false;
        }

        // Check if quote has line items
        return $quote->getLineItems() instanceof QuoteLineItemCollection && count($quote->getLineItems()) !== 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function convertCartToQuoteLineItems(Cart $cart): array
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

    private function getQuoteEntityById(string $quoteId, ?Context $context = null): QuoteEntity
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getQuoteRepository();

        $criteria = new Criteria([$quoteId]);
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');
        $criteria->addAssociation('customer');
        $criteria->addAssociation('price');

        $quote = $repository->search($criteria, $context)->first();

        if (! $quote instanceof QuoteEntity) {
            throw new \RuntimeException(sprintf('Quote with ID "%s" not found', $quoteId));
        }

        return $quote;
    }

    private function getB2bOrderById(string $orderId, Context $context): OrderEntity
    {
        /** @var EntityRepository<OrderCollection> $repository */
        $repository = static::getContainer()->get('order.repository');

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('deliveries');

        $order = $repository->search($criteria, $context)->first();

        if (! $order instanceof OrderEntity) {
            throw new \RuntimeException(sprintf('Order with ID "%s" not found', $orderId));
        }

        return $order;
    }

    /**
     * @return EntityRepository<QuoteCollection>
     */
    private function getQuoteRepository(): EntityRepository
    {
        return static::getContainer()->get('quote.repository');
    }

    /**
     * @return EntityRepository<QuoteCommentCollection>
     */
    private function getQuoteCommentRepository(): EntityRepository
    {
        return static::getContainer()->get('quote_comment.repository');
    }

    /**
     * @return EntityRepository<QuoteDocumentCollection>
     */
    private function getQuoteDocumentRepository(): EntityRepository
    {
        return static::getContainer()->get('quote_document.repository');
    }

    private function getQuoteStateMachineRegistry(): StateMachineRegistry
    {
        return static::getContainer()->get(StateMachineRegistry::class);
    }

    private function getB2bCartService(): CartService
    {
        return static::getContainer()->get(CartService::class);
    }
}
