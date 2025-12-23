<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\BudgetNotificationHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BudgetNotificationHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(BudgetEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testShouldNotify(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $budget = new BudgetEntity();
        $budget->setNotify(true);
        $budget->setSent(false);
        $budget->setNotificationConfig(['type' => 'percentage', 'value' => 80]);
        $budget->setAmount(100.0);
        $budget->setUsedAmount(90.0);

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $helper = new BudgetNotificationHelper($container);
        $result = $helper->shouldNotify('budget-id');

        $this->assertTrue($result);
    }

    public function testMarkAsSent(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $budget = new BudgetEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $repository->expects($this->once())->method('update');

        $helper = new BudgetNotificationHelper($container);
        $result = $helper->markAsSent('budget-id');

        $this->assertSame($budget, $result);
    }
}
