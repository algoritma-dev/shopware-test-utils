<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\MediaFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaFactoryTest extends TestCase
{
    public function testCreateMedia(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $media = new MediaEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($media);

        $factory = new MediaFactory($container);
        $result = $factory->create(Context::createDefaultContext());

        $this->assertInstanceOf(MediaEntity::class, $result);
    }

    public function testWithTitle(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new MediaFactory($container);

        $factory->withTitle('Test Media');

        $this->assertInstanceOf(MediaFactory::class, $factory);
    }
}
