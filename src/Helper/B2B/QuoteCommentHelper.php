<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for managing quote comments in tests.
 * Simplifies adding, retrieving, and validating quote communication.
 */
class QuoteCommentHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Add a comment to a quote.
     */
    public function addComment(
        string $quoteId,
        string $message,
        ?string $employeeId = null,
        ?Context $context = null
    ): string {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('quote_comment.repository');

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
     * Add multiple comments in sequence.
     */
    public function addComments(string $quoteId, array $messages, ?string $employeeId = null, ?Context $context = null): array
    {
        $commentIds = [];

        foreach ($messages as $message) {
            $commentIds[] = $this->addComment($quoteId, $message, $employeeId, $context);
        }

        return $commentIds;
    }

    /**
     * Get all comments for a quote.
     */
    public function getComments(string $quoteId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('quote_comment.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('quoteId', $quoteId));
        $criteria->addAssociation('employee');
        $criteria->addSorting(new FieldSorting('createdAt', 'ASC'));

        $result = $repository->search($criteria, $context);

        return array_values($result->getElements());
    }

    /**
     * Get comment count for a quote.
     */
    public function getCommentCount(string $quoteId, ?Context $context = null): int
    {
        return count($this->getComments($quoteId, $context));
    }

    /**
     * Get last comment for a quote.
     */
    public function getLastComment(string $quoteId, ?Context $context = null): ?array
    {
        $comments = $this->getComments($quoteId, $context);

        return $comments === [] ? null : end($comments);
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(string $commentId, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('quote_comment.repository');

        $repository->delete([['id' => $commentId]], $context);
    }

    /**
     * Delete all comments for a quote.
     */
    public function deleteAllComments(string $quoteId, ?Context $context = null): void
    {
        $comments = $this->getComments($quoteId, $context);

        if ($comments === []) {
            return;
        }

        $context ??= Context::createCLIContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('quote_comment.repository');

        $ids = array_map(fn ($comment): array => ['id' => $comment->getId()], $comments);
        $repository->delete($ids, $context);
    }

    /**
     * Simulate a conversation between admin and employee.
     */
    public function simulateConversation(
        string $quoteId,
        array $conversation,
        ?Context $context = null
    ): array {
        $commentIds = [];

        foreach ($conversation as $entry) {
            $message = $entry['message'];
            $employeeId = $entry['employeeId'] ?? null;

            $commentIds[] = $this->addComment($quoteId, $message, $employeeId, $context);
        }

        return $commentIds;
    }
}
