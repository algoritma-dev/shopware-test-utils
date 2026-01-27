<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\MediaHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaHelperTest extends TestCase
{
    public function testAssignToProduct(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $mediaRepo = $this->createStub(EntityRepository::class);
        $productRepo = $this->createMock(EntityRepository::class);

        $container->method('get')->willReturnMap([
            ['media.repository', 1, $mediaRepo],
            ['product.repository', 1, $productRepo],
        ]);

        $productRepo->expects($this->once())->method('update');

        $helper = new MediaHelper($container);
        $helper->assignToProduct('media-id', 'product-id', true, Context::createCLIContext());
    }

    public function testDelete(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $mediaRepo = $this->createMock(EntityRepository::class);

        $container->method('get')->willReturn($mediaRepo);
        $mediaRepo->expects($this->once())->method('delete');

        $helper = new MediaHelper($container);
        $helper->delete('media-id', Context::createCLIContext());
    }

    public function testGet(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $mediaRepo = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $media = new MediaEntity();

        $container->method('get')->willReturn($mediaRepo);
        $mediaRepo->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($media);

        $helper = new MediaHelper($container);
        $result = $helper->get('media-id');

        $this->assertSame($media, $result);
    }
}
