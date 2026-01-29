<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteCommentHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteDocumentHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteRequestHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteStateMachineHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteToOrderConverter;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteComment\QuoteCommentEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteDocument\QuoteDocumentEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionEntity;

trait B2BQuoteTrait
{
    use KernelTestBehaviour;

    private ?QuoteHelper $b2bQuoteHelperInstance = null;

    private ?QuoteStateMachineHelper $b2bQuoteStateMachineHelperInstance = null;

    private ?QuoteCommentHelper $b2bQuoteCommentHelperInstance = null;

    private ?QuoteDocumentHelper $b2bQuoteDocumentHelperInstance = null;

    private ?QuoteRequestHelper $b2bQuoteRequestHelperInstance = null;

    private ?QuoteToOrderConverter $b2bQuoteToOrderConverterInstance = null;

    protected function getB2bQuoteHelper(): QuoteHelper
    {
        if (! $this->b2bQuoteHelperInstance instanceof QuoteHelper) {
            $this->b2bQuoteHelperInstance = new QuoteHelper(static::getContainer());
        }

        return $this->b2bQuoteHelperInstance;
    }

    protected function getB2bQuoteStateMachineHelper(): QuoteStateMachineHelper
    {
        if (! $this->b2bQuoteStateMachineHelperInstance instanceof QuoteStateMachineHelper) {
            $this->b2bQuoteStateMachineHelperInstance = new QuoteStateMachineHelper(static::getContainer());
        }

        return $this->b2bQuoteStateMachineHelperInstance;
    }

    protected function getB2bQuoteCommentHelper(): QuoteCommentHelper
    {
        if (! $this->b2bQuoteCommentHelperInstance instanceof QuoteCommentHelper) {
            $this->b2bQuoteCommentHelperInstance = new QuoteCommentHelper(static::getContainer());
        }

        return $this->b2bQuoteCommentHelperInstance;
    }

    protected function getB2bQuoteDocumentHelper(): QuoteDocumentHelper
    {
        if (! $this->b2bQuoteDocumentHelperInstance instanceof QuoteDocumentHelper) {
            $this->b2bQuoteDocumentHelperInstance = new QuoteDocumentHelper(static::getContainer());
        }

        return $this->b2bQuoteDocumentHelperInstance;
    }

    protected function getB2bQuoteRequestHelper(): QuoteRequestHelper
    {
        if (! $this->b2bQuoteRequestHelperInstance instanceof QuoteRequestHelper) {
            $this->b2bQuoteRequestHelperInstance = new QuoteRequestHelper(static::getContainer());
        }

        return $this->b2bQuoteRequestHelperInstance;
    }

    protected function getB2bQuoteToOrderConverter(): QuoteToOrderConverter
    {
        if (! $this->b2bQuoteToOrderConverterInstance instanceof QuoteToOrderConverter) {
            $this->b2bQuoteToOrderConverterInstance = new QuoteToOrderConverter(static::getContainer());
        }

        return $this->b2bQuoteToOrderConverterInstance;
    }

    protected function b2bQuoteConvertToCart(QuoteEntity $quote, SalesChannelContext $context): Cart
    {
        return $this->getB2bQuoteHelper()->convertQuoteToCart($quote, $context);
    }

    protected function b2bQuoteAssertInState(string $quoteId, string $expectedState, ?Context $context = null): void
    {
        $this->getB2bQuoteHelper()->assertQuoteInState($quoteId, $expectedState, $context);
    }

    protected function b2bQuoteAssertHasComments(string $quoteId, ?int $expectedCount = null, ?Context $context = null): void
    {
        $this->getB2bQuoteHelper()->assertQuoteHasComments($quoteId, $expectedCount, $context);
    }

    protected function b2bQuoteAssertCanBeConverted(string $quoteId, ?Context $context = null): void
    {
        $this->getB2bQuoteHelper()->assertQuoteCanBeConverted($quoteId, $context);
    }

    protected function b2bQuoteTransition(string $quoteId, string $action, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->transition($quoteId, $action, $context);
    }

    protected function b2bQuoteRequest(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->requestQuote($quoteId, $context);
    }

    protected function b2bQuoteSend(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->sendQuote($quoteId, $context);
    }

    protected function b2bQuoteAccept(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->acceptQuote($quoteId, $context);
    }

    protected function b2bQuoteDecline(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->declineQuote($quoteId, $context);
    }

    protected function b2bQuoteExpire(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->expireQuote($quoteId, $context);
    }

    protected function b2bQuoteRequestChanges(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->requestChanges($quoteId, $context);
    }

    protected function b2bQuoteReopen(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->reopenQuote($quoteId, $context);
    }

    protected function b2bQuoteGetCurrentState(string $quoteId, ?Context $context = null): string
    {
        return $this->getB2bQuoteStateMachineHelper()->getCurrentState($quoteId, $context);
    }

    protected function b2bQuoteIsInState(string $quoteId, string $state, ?Context $context = null): bool
    {
        return $this->getB2bQuoteStateMachineHelper()->isInState($quoteId, $state, $context);
    }

    /**
     * @return array<int, StateMachineTransitionEntity>
     */
    protected function b2bQuoteGetAvailableTransitions(string $quoteId, ?Context $context = null): array
    {
        return $this->getB2bQuoteStateMachineHelper()->getAvailableTransitions($quoteId, $context);
    }

    protected function b2bQuoteSimulateFullAcceptanceWorkflow(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->simulateFullAcceptanceWorkflow($quoteId, $context);
    }

    protected function b2bQuoteSimulateFullDeclineWorkflow(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->simulateFullDeclineWorkflow($quoteId, $context);
    }

    protected function b2bQuoteSimulateNegotiationWorkflow(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->getB2bQuoteStateMachineHelper()->simulateNegotiationWorkflow($quoteId, $context);
    }

    protected function b2bQuoteAddComment(
        string $quoteId,
        string $message,
        ?string $employeeId = null,
        ?Context $context = null
    ): string {
        return $this->getB2bQuoteCommentHelper()->addComment($quoteId, $message, $employeeId, $context);
    }

    /**
     * @param array<string> $messages
     *
     * @return array<string>
     */
    protected function b2bQuoteAddComments(
        string $quoteId,
        array $messages,
        ?string $employeeId = null,
        ?Context $context = null
    ): array {
        return $this->getB2bQuoteCommentHelper()->addComments($quoteId, $messages, $employeeId, $context);
    }

    /**
     * @return array<QuoteCommentEntity>
     */
    protected function b2bQuoteGetComments(string $quoteId, ?Context $context = null): array
    {
        return $this->getB2bQuoteCommentHelper()->getComments($quoteId, $context);
    }

    protected function b2bQuoteGetCommentCount(string $quoteId, ?Context $context = null): int
    {
        return $this->getB2bQuoteCommentHelper()->getCommentCount($quoteId, $context);
    }

    protected function b2bQuoteGetLastComment(string $quoteId, ?Context $context = null): ?QuoteCommentEntity
    {
        return $this->getB2bQuoteCommentHelper()->getLastComment($quoteId, $context);
    }

    protected function b2bQuoteDeleteComment(string $commentId, ?Context $context = null): void
    {
        $this->getB2bQuoteCommentHelper()->deleteComment($commentId, $context);
    }

    protected function b2bQuoteDeleteAllComments(string $quoteId, ?Context $context = null): void
    {
        $this->getB2bQuoteCommentHelper()->deleteAllComments($quoteId, $context);
    }

    /**
     * @param array<int, array<string, string>> $conversation
     *
     * @return array<string>
     */
    protected function b2bQuoteSimulateConversation(
        string $quoteId,
        array $conversation,
        ?Context $context = null
    ): array {
        return $this->getB2bQuoteCommentHelper()->simulateConversation($quoteId, $conversation, $context);
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function b2bQuoteGenerateDocument(
        string $quoteId,
        string $documentType = 'quote',
        array $config = [],
        ?Context $context = null
    ): string {
        return $this->getB2bQuoteDocumentHelper()->generateDocument(
            $quoteId,
            $documentType,
            $config,
            $context
        );
    }

    /**
     * @return array<QuoteDocumentEntity>
     */
    protected function b2bQuoteGetDocuments(string $quoteId, ?Context $context = null): array
    {
        return $this->getB2bQuoteDocumentHelper()->getDocuments($quoteId, $context);
    }

    protected function b2bQuoteGetDocument(string $documentId, ?Context $context = null): ?QuoteDocumentEntity
    {
        return $this->getB2bQuoteDocumentHelper()->getDocument($documentId, $context);
    }

    protected function b2bQuoteDeleteDocument(string $documentId, ?Context $context = null): void
    {
        $this->getB2bQuoteDocumentHelper()->deleteDocument($documentId, $context);
    }

    protected function b2bQuoteHasDocuments(string $quoteId, ?Context $context = null): bool
    {
        return $this->getB2bQuoteDocumentHelper()->hasDocuments($quoteId, $context);
    }

    protected function b2bQuoteGetDocumentCount(string $quoteId, ?Context $context = null): int
    {
        return $this->getB2bQuoteDocumentHelper()->getDocumentCount($quoteId, $context);
    }

    protected function b2bQuoteGetLatestDocument(string $quoteId, ?Context $context = null): ?QuoteDocumentEntity
    {
        return $this->getB2bQuoteDocumentHelper()->getLatestDocument($quoteId, $context);
    }

    /**
     * @return array<string>
     */
    protected function b2bQuoteRegenerateDocuments(string $quoteId, ?Context $context = null): array
    {
        return $this->getB2bQuoteDocumentHelper()->regenerateDocuments($quoteId, $context);
    }

    /**
     * @param array<string, mixed> $additionalData
     */
    protected function b2bQuoteRequestFromCart(
        Cart $cart,
        SalesChannelContext $context,
        array $additionalData = []
    ): string {
        return $this->getB2bQuoteRequestHelper()->requestQuote($cart, $context, $additionalData);
    }

    protected function b2bQuoteRequestFromCartWithComment(Cart $cart, SalesChannelContext $context, string $comment): string
    {
        return $this->getB2bQuoteRequestHelper()->requestQuoteWithComment($cart, $context, $comment);
    }

    protected function b2bQuoteConvertToOrder(string $quoteId, SalesChannelContext $context): OrderEntity
    {
        return $this->getB2bQuoteToOrderConverter()->convertToOrder($quoteId, $context);
    }

    protected function b2bQuoteCreateCartFromQuote(QuoteEntity $quote, SalesChannelContext $context): Cart
    {
        return $this->getB2bQuoteToOrderConverter()->createCartFromQuote($quote, $context);
    }

    protected function b2bQuoteAcceptAndConvertToOrder(
        string $quoteId,
        SalesChannelContext $context,
        ?QuoteStateMachineHelper $stateMachineHelper = null
    ): OrderEntity {
        return $this->getB2bQuoteToOrderConverter()->acceptAndConvertToOrder(
            $quoteId,
            $context,
            $stateMachineHelper
        );
    }

    protected function b2bQuoteGetTotal(string $quoteId, ?Context $context = null): float
    {
        return $this->getB2bQuoteToOrderConverter()->getQuoteTotal($quoteId, $context);
    }

    protected function b2bQuoteCanConvertToOrder(string $quoteId, ?Context $context = null): bool
    {
        return $this->getB2bQuoteToOrderConverter()->canConvertToOrder($quoteId, $context);
    }
}
