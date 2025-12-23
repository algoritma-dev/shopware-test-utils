<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\OrderApproval\Domain\State\PendingOrderStates;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for testing approval workflows.
 * Simulates multi-step approval processes and state transitions.
 */
class ApprovalWorkflowHelper
{
    private readonly StateMachineRegistry $stateMachineRegistry;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->stateMachineRegistry = $this->container->get(StateMachineRegistry::class);
    }

    /**
     * Approve a pending order.
     */
    public function approvePendingOrder(string $pendingOrderId, ?Context $context = null): PendingOrderEntity
    {
        $context ??= Context::createCLIContext();

        $transition = new Transition(
            'b2b_components_pending_order',
            $pendingOrderId,
            PendingOrderStates::ACTION_APPROVE,
            'stateId'
        );

        $this->stateMachineRegistry->transition($transition, $context);

        return $this->loadPendingOrder($pendingOrderId, $context);
    }

    /**
     * Decline a pending order.
     */
    public function declinePendingOrder(string $pendingOrderId, ?string $reason = null, ?Context $context = null): PendingOrderEntity
    {
        $context ??= Context::createCLIContext();

        // Update reason if provided
        if ($reason) {
            $this->updatePendingOrderReason($pendingOrderId, $reason, $context);
        }

        $transition = new Transition(
            'b2b_components_pending_order',
            $pendingOrderId,
            PendingOrderStates::ACTION_DECLINE,
            'stateId'
        );

        $this->stateMachineRegistry->transition($transition, $context);

        return $this->loadPendingOrder($pendingOrderId, $context);
    }

    /**
     * Convert pending order to actual order (after approval).
     */
    public function convertToOrder(string $pendingOrderId, ?Context $context = null): OrderEntity
    {
        $context ??= Context::createCLIContext();

        $pendingOrder = $this->loadPendingOrder($pendingOrderId, $context);

        // Ensure pending order is approved
        $state = $pendingOrder->getStateMachineState()->getTechnicalName();
        if ($state !== PendingOrderStates::STATE_APPROVED) {
            throw new \RuntimeException(sprintf('Pending order must be approved before conversion. Current state: %s', $state));
        }

        // Mark as ordered
        $transition = new Transition(
            'b2b_components_pending_order',
            $pendingOrderId,
            PendingOrderStates::ACTION_ORDER,
            'stateId'
        );

        $this->stateMachineRegistry->transition($transition, $context);

        // Get the created order
        return $this->getOrderFromPendingOrder($pendingOrderId, $context);
    }

    /**
     * Simulate full approval workflow: pending → approved → ordered.
     */
    public function simulateFullApprovalWorkflow(string $pendingOrderId, ?Context $context = null): OrderEntity
    {
        $this->approvePendingOrder($pendingOrderId, $context);

        return $this->convertToOrder($pendingOrderId, $context);
    }

    /**
     * Simulate rejection workflow: pending → declined.
     */
    public function simulateRejectionWorkflow(string $pendingOrderId, string $reason = 'Budget exceeded', ?Context $context = null): PendingOrderEntity
    {
        return $this->declinePendingOrder($pendingOrderId, $reason, $context);
    }

    /**
     * Get all pending orders for an employee.
     *
     * @return array<PendingOrderEntity>
     */
    public function getPendingOrdersForEmployee(string $employeeId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<PendingOrderEntity> $repository */
        $repository = $this->container->get('b2b_components_pending_order.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('employeeId', $employeeId));
        $criteria->addFilter(new EqualsFilter('stateMachineState.technicalName', PendingOrderStates::STATE_PENDING));
        $criteria->addAssociation('approvalRule');
        $criteria->addAssociation('employee');

        $result = $repository->search($criteria, $context);

        return array_values($result->getElements());
    }

    /**
     * Get all pending orders requiring approval.
     *
     * @return array<PendingOrderEntity>
     */
    public function getAllPendingApprovals(?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<PendingOrderEntity> $repository */
        $repository = $this->container->get('b2b_components_pending_order.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('stateMachineState.technicalName', PendingOrderStates::STATE_PENDING));
        $criteria->addAssociation('approvalRule');
        $criteria->addAssociation('employee');
        $criteria->addAssociation('customer');

        $result = $repository->search($criteria, $context);

        return array_values($result->getElements());
    }

    /**
     * Check if pending order can be approved.
     */
    public function canApprove(string $pendingOrderId, ?Context $context = null): bool
    {
        $pendingOrder = $this->loadPendingOrder($pendingOrderId, $context);
        $state = $pendingOrder->getStateMachineState()->getTechnicalName();

        return $state === PendingOrderStates::STATE_PENDING;
    }

    /**
     * Get current state of pending order.
     */
    public function getCurrentState(string $pendingOrderId, ?Context $context = null): string
    {
        $pendingOrder = $this->loadPendingOrder($pendingOrderId, $context);

        return $pendingOrder->getStateMachineState()->getTechnicalName();
    }

    private function loadPendingOrder(string $pendingOrderId, Context $context): PendingOrderEntity
    {
        /** @var EntityRepository<PendingOrderEntity> $repository */
        $repository = $this->container->get('b2b_components_pending_order.repository');

        $criteria = new Criteria([$pendingOrderId]);
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('approvalRule');
        $criteria->addAssociation('employee');
        $criteria->addAssociation('customer');
        $criteria->addAssociation('order');

        /** @var PendingOrderEntity|null $entity */
        $entity = $repository->search($criteria, $context)->first();

        if (! $entity) {
            throw new \RuntimeException(sprintf('PendingOrder with ID "%s" not found', $pendingOrderId));
        }

        return $entity;
    }

    private function updatePendingOrderReason(string $pendingOrderId, string $reason, Context $context): void
    {
        /** @var EntityRepository<PendingOrderEntity> $repository */
        $repository = $this->container->get('b2b_components_pending_order.repository');

        $repository->update([
            [
                'id' => $pendingOrderId,
                'reason' => $reason,
            ],
        ], $context);
    }

    private function getOrderFromPendingOrder(string $pendingOrderId, Context $context): OrderEntity
    {
        $pendingOrder = $this->loadPendingOrder($pendingOrderId, $context);

        if (! $pendingOrder->getOrderId()) {
            throw new \RuntimeException('Pending order has not been converted to an order yet');
        }

        /** @var EntityRepository<OrderEntity> $orderRepository */
        $orderRepository = $this->container->get('order.repository');

        $criteria = new Criteria([$pendingOrder->getOrderId()]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('deliveries');

        /** @var OrderEntity|null $order */
        $order = $orderRepository->search($criteria, $context)->first();

        if (! $order) {
            throw new \RuntimeException(sprintf('Order with ID "%s" not found', $pendingOrder->getOrderId()));
        }

        return $order;
    }
}
