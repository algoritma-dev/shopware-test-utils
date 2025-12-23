<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteCommentHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuoteCommentHelperTest extends TestCase
{
    public function testAddComment(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);

        $container->method('get')->willReturn($repository);
        $repository->expects($this->once())->method('create');

        $helper = new QuoteCommentHelper($container);
        $result = $helper->addComment('quote-id', 'test comment');

        $this->assertIsString($result);
    }

    public function testGetComments(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('getElements')->willReturn([new \stdClass()]);

        $helper = new QuoteCommentHelper($container);
        $result = $helper->getComments('quote-id');

        $this->assertCount(1, $result);
    }

    public function testDeleteComment(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);

        $container->method('get')->willReturn($repository);
        $repository->expects($this->once())->method('delete');

        $helper = new QuoteCommentHelper($container);
        $helper->deleteComment('comment-id');
    }
}
