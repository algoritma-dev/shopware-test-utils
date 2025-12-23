<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\BudgetRenewHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEnum;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BudgetRenewHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(BudgetEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testRenewBudget(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $budget = new BudgetEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $repository->expects($this->once())->method('update');

        $helper = new BudgetRenewHelper($container);
        $result = $helper->renewBudget('budget-id');

        $this->assertSame($budget, $result);
    }

    public function testShouldRenew(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $budget = new BudgetEntity();
        $budget->setRenewsType(BudgetEnum::RENEWS_TYPE_MONTHLY);
        $budget->setLastRenews(new \DateTime('-2 months'));

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $helper = new BudgetRenewHelper($container);
        $result = $helper->shouldRenew('budget-id');

        $this->assertTrue($result);
    }
}
