<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for performing actions on existing media entities.
 * Does NOT create media - use MediaFactory for that.
 *
 * Responsibilities:
 * - Assign media to products/entities
 * - Delete media
 * - Retrieve media information
 * - Update media metadata
 */
class MediaHelper
{
    private readonly EntityRepository $mediaRepository;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->mediaRepository = $this->container->get('media.repository');
    }

    /**
     * Assigns media to a product.
     * This is an ACTION on existing entities.
     */
    public function assignToProduct(string $mediaId, string $productId, bool $setCover = false, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $productRepository = $this->container->get('product.repository');

        $data = [
            'id' => $productId,
            'media' => [
                [
                    'mediaId' => $mediaId,
                ],
            ],
        ];

        if ($setCover) {
            $data['coverId'] = $mediaId;
        }

        $productRepository->update([$data], $context);
    }

    /**
     * Deletes a media file.
     */
    public function delete(string $mediaId, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $this->mediaRepository->delete([['id' => $mediaId]], $context);
    }

    /**
     * Gets a media entity by ID.
     */
    public function get(string $mediaId, ?Context $context = null): ?MediaEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        return $this->mediaRepository->search(new Criteria([$mediaId]), $context)->first();
    }

    /**
     * Updates media metadata (alt text, title, etc.).
     */
    public function updateMetadata(string $mediaId, array $metadata, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $data = array_merge(['id' => $mediaId], $metadata);
        $this->mediaRepository->update([$data], $context);
    }

    /**
     * Moves media to a different folder.
     */
    public function moveToFolder(string $mediaId, string $folderId, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $this->mediaRepository->update([
            [
                'id' => $mediaId,
                'mediaFolderId' => $folderId,
            ],
        ], $context);
    }

    /**
     * Checks if media exists.
     */
    public function exists(string $mediaId, ?Context $context = null): bool
    {
        return $this->get($mediaId, $context) instanceof MediaEntity;
    }

    /**
     * Gets media URL.
     */
    public function getUrl(MediaEntity $media): ?string
    {
        return $media->getUrl();
    }

    /**
     * Bulk deletes media files.
     */
    public function bulkDelete(array $mediaIds, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $deleteData = array_map(fn ($id): array => ['id' => $id], $mediaIds);
        $this->mediaRepository->delete($deleteData, $context);
    }
}
