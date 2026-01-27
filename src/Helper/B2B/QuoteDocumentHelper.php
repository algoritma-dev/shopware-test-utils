<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteDocument\QuoteDocumentCollection;
use Shopware\Commercial\B2B\QuoteManagement\Entity\QuoteDocument\QuoteDocumentEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for managing quote documents (PDFs) in tests.
 * Handles quote document generation and retrieval.
 */
class QuoteDocumentHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Generate a document for a quote.
     *
     * @param array<string, mixed> $config
     */
    public function generateDocument(
        string $quoteId,
        string $documentType = 'quote',
        array $config = [],
        ?Context $context = null
    ): string {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<QuoteDocumentCollection> $repository */
        $repository = $this->container->get('quote_document.repository');

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
     * Get all documents for a quote.
     *
     * @return array<QuoteDocumentEntity>
     */
    public function getDocuments(string $quoteId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<QuoteDocumentCollection> $repository */
        $repository = $this->container->get('quote_document.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('quoteId', $quoteId));
        $criteria->addAssociation('quote');

        $result = $repository->search($criteria, $context);

        /** @var array<string, QuoteDocumentEntity> $elements */
        $elements = $result->getElements();

        return array_values($elements);
    }

    /**
     * Get document by ID.
     */
    public function getDocument(string $documentId, ?Context $context = null): ?QuoteDocumentEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<QuoteDocumentCollection> $repository */
        $repository = $this->container->get('quote_document.repository');

        $criteria = new Criteria([$documentId]);
        $criteria->addAssociation('quote');

        $entity = $repository->search($criteria, $context)->first();

        return $entity instanceof QuoteDocumentEntity ? $entity : null;
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(string $documentId, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<QuoteDocumentCollection> $repository */
        $repository = $this->container->get('quote_document.repository');

        $repository->delete([['id' => $documentId]], $context);
    }

    /**
     * Check if quote has any documents.
     */
    public function hasDocuments(string $quoteId, ?Context $context = null): bool
    {
        return count($this->getDocuments($quoteId, $context)) > 0;
    }

    /**
     * Get document count for a quote.
     */
    public function getDocumentCount(string $quoteId, ?Context $context = null): int
    {
        return count($this->getDocuments($quoteId, $context));
    }

    /**
     * Get latest document for a quote.
     */
    public function getLatestDocument(string $quoteId, ?Context $context = null): ?QuoteDocumentEntity
    {
        $documents = $this->getDocuments($quoteId, $context);

        if ($documents === []) {
            return null;
        }

        // Sort by created date descending
        usort($documents, fn (QuoteDocumentEntity $a, QuoteDocumentEntity $b): int => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $documents[0];
    }

    /**
     * Regenerate all documents for a quote.
     *
     * @return array<string>
     */
    public function regenerateDocuments(string $quoteId, ?Context $context = null): array
    {
        // Delete existing documents
        $existingDocuments = $this->getDocuments($quoteId, $context);
        foreach ($existingDocuments as $doc) {
            $this->deleteDocument($doc->getId(), $context);
        }

        // Generate new document
        $documentId = $this->generateDocument($quoteId, 'quote', [], $context);

        return [$documentId];
    }
}
