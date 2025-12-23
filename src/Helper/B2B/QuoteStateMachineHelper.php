<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for testing quote state machine transitions.
 * Simplifies state changes and validates quote workflow.
 */
class QuoteStateMachineHelper
{
    private readonly StateMachineRegistry $stateMachineRegistry;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->stateMachineRegistry = $this->container->get(StateMachineRegistry::class);
    }

    /**
     * Transition a quote to a specific state using action.
     */
    public function transition(string $quoteId, string $action, ?Context $context = null): QuoteEntity
    {
        $context ??= Context::createCLIContext();

        $transition = new Transition(
            'quote',
            $quoteId,
            $action,
            'stateId'
        );

        $this->stateMachineRegistry->transition($transition, $context);

        return $this->loadQuote($quoteId, $context);
    }

    /**
     * Move quote from draft to open (customer submits quote request).
     */
    public function requestQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transition($quoteId, QuoteStates::ACTION_PROCESS, $context);
    }

    /**
     * Admin sends quote to customer (open → replied).
     */
    public function sendQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transition($quoteId, QuoteStates::ACTION_SENT, $context);
    }

    /**
     * Customer accepts the quote (replied → accepted).
     */
    public function acceptQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transition($quoteId, QuoteStates::ACTION_ACCEPT, $context);
    }

    /**
     * Customer declines the quote (replied → declined).
     */
    public function declineQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transition($quoteId, QuoteStates::ACTION_DECLINE, $context);
    }

    /**
     * Quote expires (any state → expired).
     */
    public function expireQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transition($quoteId, QuoteStates::ACTION_EXPIRE, $context);
    }

    /**
     * Customer requests changes (replied → in_review).
     */
    public function requestChanges(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transition($quoteId, QuoteStates::ACTION_REQUEST_CHANGE, $context);
    }

    /**
     * Reopen a declined/expired quote.
     */
    public function reopenQuote(string $quoteId, ?Context $context = null): QuoteEntity
    {
        return $this->transition($quoteId, QuoteStates::ACTION_REOPEN, $context);
    }

    /**
     * Get current state of a quote.
     */
    public function getCurrentState(string $quoteId, ?Context $context = null): string
    {
        $quote = $this->loadQuote($quoteId, $context);

        return $quote->getStateMachineState()->getTechnicalName();
    }

    /**
     * Check if quote is in a specific state.
     */
    public function isInState(string $quoteId, string $state, ?Context $context = null): bool
    {
        return $this->getCurrentState($quoteId, $context) === $state;
    }

    /**
     * Get available transitions for a quote.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableTransitions(string $quoteId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        return $this->stateMachineRegistry->getAvailableTransitions('quote', $quoteId, 'stateId', $context);
    }

    /**
     * Simulate full quote workflow: draft → open → replied → accepted.
     */
    public function simulateFullAcceptanceWorkflow(string $quoteId, ?Context $context = null): QuoteEntity
    {
        $this->requestQuote($quoteId, $context);
        $this->sendQuote($quoteId, $context);

        return $this->acceptQuote($quoteId, $context);
    }

    /**
     * Simulate full quote workflow: draft → open → replied → declined.
     */
    public function simulateFullDeclineWorkflow(string $quoteId, ?Context $context = null): QuoteEntity
    {
        $this->requestQuote($quoteId, $context);
        $this->sendQuote($quoteId, $context);

        return $this->declineQuote($quoteId, $context);
    }

    /**
     * Simulate negotiation workflow: draft → open → replied → in_review → replied → accepted.
     */
    public function simulateNegotiationWorkflow(string $quoteId, ?Context $context = null): QuoteEntity
    {
        $this->requestQuote($quoteId, $context);
        $this->sendQuote($quoteId, $context);
        $this->requestChanges($quoteId, $context);
        $this->sendQuote($quoteId, $context);

        return $this->acceptQuote($quoteId, $context);
    }

    private function loadQuote(string $quoteId, ?Context $context): QuoteEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<QuoteEntity> $repository */
        $repository = $this->container->get('quote.repository');

        $criteria = new Criteria([$quoteId]);
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('lineItems');

        /** @var QuoteEntity|null $quote */
        $quote = $repository->search($criteria, $context)->first();

        if (! $quote) {
            throw new \RuntimeException(sprintf('Quote with ID "%s" not found', $quoteId));
        }

        return $quote;
    }
}
