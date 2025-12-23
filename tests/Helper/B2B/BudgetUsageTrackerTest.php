<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\BudgetUsageTracker;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BudgetUsageTrackerTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(BudgetEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testTrackUsage(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $budget = new BudgetEntity();

        $budget->setUsedAmount(50.0);

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $repository->expects($this->once())->method('update');

        $tracker = new BudgetUsageTracker($container);
        $result = $tracker->trackUsage('budget-id', 10.0);

        $this->assertSame($budget, $result);
    }

    public function testFillToPercentage(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $budget = new BudgetEntity();

        $budget->setAmount(100.0);
        $budget->setUsedAmount(0.0);

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $repository->expects($this->once())->method('update');

        $tracker = new BudgetUsageTracker($container);
        $tracker->fillToPercentage('budget-id', 50.0);
    }
}
