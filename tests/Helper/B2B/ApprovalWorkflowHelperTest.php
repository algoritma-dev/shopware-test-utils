<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\ApprovalWorkflowHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApprovalWorkflowHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(PendingOrderEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testApprovePendingOrder(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $registry = $this->createMock(StateMachineRegistry::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $pendingOrder = new PendingOrderEntity();
        $state = new StateMachineStateEntity();

        $state->setTechnicalName('pending');
        $pendingOrder->setStateMachineState($state);

        $container->method('get')->willReturnMap([
            [StateMachineRegistry::class, 1, $registry],
            ['b2b_components_pending_order.repository', 1, $repository],
        ]);

        $registry->expects($this->once())->method('transition');

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($pendingOrder);

        $helper = new ApprovalWorkflowHelper($container);
        $result = $helper->approvePendingOrder('pending-id');

        $this->assertSame($pendingOrder, $result);
    }

    public function testDeclinePendingOrder(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $registry = $this->createMock(StateMachineRegistry::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $pendingOrder = new PendingOrderEntity();

        $container->method('get')->willReturnMap([
            [StateMachineRegistry::class, 1, $registry],
            ['b2b_components_pending_order.repository', 1, $repository],
        ]);

        $registry->expects($this->once())->method('transition');

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($pendingOrder);

        $helper = new ApprovalWorkflowHelper($container);
        $result = $helper->declinePendingOrder('pending-id', 'reason');

        $this->assertSame($pendingOrder, $result);
    }
}
