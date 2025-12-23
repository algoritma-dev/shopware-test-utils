<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\QuoteStateMachineHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuoteStateMachineHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(QuoteEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testTransition(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $registry = $this->createMock(StateMachineRegistry::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $quote = new QuoteEntity();
        $state = new StateMachineStateEntity();

        $state->setTechnicalName('open');
        $quote->setStateMachineState($state);

        $container->method('get')->willReturnMap([
            [StateMachineRegistry::class, 1, $registry],
            ['quote.repository', 1, $repository],
        ]);

        $registry->expects($this->once())->method('transition');

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($quote);

        $helper = new QuoteStateMachineHelper($container);
        $result = $helper->transition('quote-id', 'process');

        $this->assertSame($quote, $result);
    }

    public function testRequestQuote(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $registry = $this->createStub(StateMachineRegistry::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $quote = new QuoteEntity();

        $container->method('get')->willReturnMap([
            [StateMachineRegistry::class, 1, $registry],
            ['quote.repository', 1, $repository],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($quote);

        $helper = new QuoteStateMachineHelper($container);
        $result = $helper->requestQuote('quote-id');

        $this->assertSame($quote, $result);
    }
}
