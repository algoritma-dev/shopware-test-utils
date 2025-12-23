<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteDocumentHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuoteDocumentHelperTest extends TestCase
{
    public function testGenerateDocument(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);

        $container->method('get')->willReturn($repository);
        $repository->expects($this->once())->method('create');

        $helper = new QuoteDocumentHelper($container);
        $result = $helper->generateDocument('quote-id');

        $this->assertIsString($result);
    }

    public function testGetDocuments(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('getElements')->willReturn([new \stdClass()]);

        $helper = new QuoteDocumentHelper($container);
        $result = $helper->getDocuments('quote-id');

        $this->assertCount(1, $result);
    }

    public function testDeleteDocument(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);

        $container->method('get')->willReturn($repository);
        $repository->expects($this->once())->method('delete');

        $helper = new QuoteDocumentHelper($container);
        $helper->deleteDocument('doc-id');
    }
}
