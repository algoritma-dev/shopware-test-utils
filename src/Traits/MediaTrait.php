<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * Trait for performing actions on existing media entities.
 */
trait MediaTrait
{
    use KernelTestBehaviour;

    /**
     * Assigns media to a product.
     */
    protected function assignMediaToProduct(string $mediaId, string $productId, bool $setCover = false, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $productRepository = static::getContainer()->get('product.repository');

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
    protected function deleteMedia(string $mediaId, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $this->getMediaRepository()->delete([['id' => $mediaId]], $context);
    }

    /**
     * Gets a media entity by ID.
     */
    protected function getMedia(string $mediaId, ?Context $context = null): ?MediaEntity
    {
        $context ??= Context::createCLIContext();
        $entity = $this->getMediaRepository()->search(new Criteria([$mediaId]), $context)->first();

        return $entity instanceof MediaEntity ? $entity : null;
    }

    /**
     * Updates media metadata (alt text, title, etc.).
     *
     * @param array<string, mixed> $metadata
     */
    protected function updateMediaMetadata(string $mediaId, array $metadata, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $data = array_merge(['id' => $mediaId], $metadata);
        $this->getMediaRepository()->update([$data], $context);
    }

    /**
     * Moves media to a different folder.
     */
    protected function moveMediaToFolder(string $mediaId, string $folderId, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $this->getMediaRepository()->update([
            [
                'id' => $mediaId,
                'mediaFolderId' => $folderId,
            ],
        ], $context);
    }

    /**
     * Checks if media exists.
     */
    protected function mediaExists(string $mediaId, ?Context $context = null): bool
    {
        return $this->getMedia($mediaId, $context) instanceof MediaEntity;
    }

    /**
     * Bulk deletes media files.
     *
     * @param array<int, string> $mediaIds
     */
    protected function bulkDeleteMedia(array $mediaIds, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $deleteData = array_map(fn (string $id): array => ['id' => $id], $mediaIds);
        $this->getMediaRepository()->delete($deleteData, $context);
    }

    /**
     * @return EntityRepository<MediaCollection>
     */
    private function getMediaRepository(): EntityRepository
    {
        return static::getContainer()->get('media.repository');
    }
}
