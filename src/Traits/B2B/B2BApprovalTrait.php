<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\PendingOrderFactory;
use PHPUnit\Framework\Assert;
use Shopware\Commercial\B2B\OrderApproval\Domain\CartToPendingOrder\PendingOrderRequestedRoute;
use Shopware\Commercial\B2B\OrderApproval\Domain\State\PendingOrderStates;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderCollection;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

/**
 * Trait for B2B order approval operations and assertions.
 */
trait B2BApprovalTrait
{
    use KernelTestBehaviour;

    protected function approvePendingOrder(string $pendingOrderId, ?Context $context = null): PendingOrderEntity
    {
        $context ??= Context::createCLIContext();

        $transition = new Transition(
            'b2b_components_pending_order',
            $pendingOrderId,
            PendingOrderStates::ACTION_APPROVE,
            'stateId'
        );

        $this->getB2bStateMachineRegistry()->transition($transition, $context);

        return $this->getPendingOrderById($pendingOrderId, $context);
    }

    protected function declinePendingOrder(
        string $pendingOrderId,
        ?string $reason = null,
        ?Context $context = null
    ): PendingOrderEntity {
        $context ??= Context::createCLIContext();

        if ($reason) {
            $this->updatePendingOrderReason($pendingOrderId, $reason, $context);
        }

        $transition = new Transition(
            'b2b_components_pending_order',
            $pendingOrderId,
            PendingOrderStates::ACTION_DECLINE,
            'stateId'
        );

        $this->getB2bStateMachineRegistry()->transition($transition, $context);

        return $this->getPendingOrderById($pendingOrderId, $context);
    }

    protected function convertPendingOrderToOrder(string $pendingOrderId, ?Context $context = null): OrderEntity
    {
        $context ??= Context::createCLIContext();

        $pendingOrder = $this->getPendingOrderById($pendingOrderId, $context);
        $state = $pendingOrder->getStateMachineState()->getTechnicalName();

        if ($state !== PendingOrderStates::STATE_APPROVED) {
            throw new \RuntimeException(sprintf('Pending order must be approved before conversion. Current state: %s', $state));
        }

        $transition = new Transition(
            'b2b_components_pending_order',
            $pendingOrderId,
            PendingOrderStates::ACTION_ORDER,
            'stateId'
        );

        $this->getB2bStateMachineRegistry()->transition($transition, $context);

        return $this->getOrderFromPendingOrder($pendingOrderId, $context);
    }

    protected function simulateFullApprovalWorkflow(string $pendingOrderId, ?Context $context = null): OrderEntity
    {
        $this->approvePendingOrder($pendingOrderId, $context);

        return $this->convertPendingOrderToOrder($pendingOrderId, $context);
    }

    protected function simulateRejectionWorkflow(
        string $pendingOrderId,
        string $reason = 'Budget exceeded',
        ?Context $context = null
    ): PendingOrderEntity {
        return $this->declinePendingOrder($pendingOrderId, $reason, $context);
    }

    /**
     * @return array<PendingOrderEntity>
     */
    protected function getPendingOrdersForEmployee(string $employeeId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        $repository = $this->getPendingOrderRepository();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('employeeId', $employeeId));
        $criteria->addFilter(new EqualsFilter('stateMachineState.technicalName', PendingOrderStates::STATE_PENDING));
        $criteria->addAssociation('approvalRule');
        $criteria->addAssociation('employee');

        /** @var array<string, PendingOrderEntity> $elements */
        $elements = $repository->search($criteria, $context)->getElements();

        return array_values($elements);
    }

    /**
     * @return array<PendingOrderEntity>
     */
    protected function getAllPendingApprovals(?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        $repository = $this->getPendingOrderRepository();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('stateMachineState.technicalName', PendingOrderStates::STATE_PENDING));
        $criteria->addAssociation('approvalRule');
        $criteria->addAssociation('employee');
        $criteria->addAssociation('customer');

        /** @var array<string, PendingOrderEntity> $elements */
        $elements = $repository->search($criteria, $context)->getElements();

        return array_values($elements);
    }

    protected function canPendingOrderBeApproved(string $pendingOrderId, ?Context $context = null): bool
    {
        $pendingOrder = $this->getPendingOrderById($pendingOrderId, $context);
        $state = $pendingOrder->getStateMachineState()->getTechnicalName();

        return $state === PendingOrderStates::STATE_PENDING;
    }

    protected function getPendingOrderState(string $pendingOrderId, ?Context $context = null): string
    {
        $pendingOrder = $this->getPendingOrderById($pendingOrderId, $context);

        return $pendingOrder->getStateMachineState()->getTechnicalName();
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function requestPendingOrder(
        SalesChannelContext $context,
        CustomerEntity $customer,
        array $data = []
    ): PendingOrderEntity {
        /** @var PendingOrderRequestedRoute $route */
        $route = static::getContainer()->get(PendingOrderRequestedRoute::class);

        $dataBag = new RequestDataBag($data);
        // @phpstan-ignore-next-line
        $response = $route->request($context, $customer, $dataBag);

        return $response->getPendingOrder();
    }

    protected function assertOrderNeedsApproval(string $orderId, ?Context $context = null): void
    {
        $pendingOrder = $this->getPendingOrderByOrderId($orderId, $context);

        Assert::assertInstanceOf(
            PendingOrderEntity::class,
            $pendingOrder,
            sprintf('Expected order "%s" to require approval, but no pending order found', $orderId)
        );
    }

    protected function assertPendingOrderCreated(string $employeeId, ?Context $context = null): void
    {
        $pendingOrders = $this->getPendingOrdersByEmployee($employeeId, $context);

        Assert::assertGreaterThan(
            0,
            count($pendingOrders),
            sprintf('Expected pending order to be created for employee "%s", but none found', $employeeId)
        );
    }

    protected function assertPendingOrderInState(
        string $pendingOrderId,
        string $expectedState,
        ?Context $context = null
    ): void {
        $pendingOrder = $this->getPendingOrderById($pendingOrderId, $context);
        $actualState = $pendingOrder->getStateMachineState()->getTechnicalName();

        Assert::assertSame(
            $expectedState,
            $actualState,
            sprintf('Expected pending order to be in state "%s", but got "%s"', $expectedState, $actualState)
        );
    }

    protected function requestOrderApproval(
        Cart $cart,
        SalesChannelContext $context,
        string $employeeId,
        ?string $approvalRuleId = null
    ): string {
        return PendingOrderFactory::fromCart(
            static::getContainer(),
            $cart,
            $context,
            $employeeId,
            $approvalRuleId
        )->getId();
    }

    protected function requestOrderApprovalWithReason(
        Cart $cart,
        SalesChannelContext $context,
        string $employeeId,
        string $reason,
        ?string $approvalRuleId = null
    ): string {
        $factory = new PendingOrderFactory(static::getContainer());
        $factory->withCart($cart, $context)
            ->withEmployee($employeeId)
            ->withReason($reason);

        if ($approvalRuleId) {
            $factory->withApprovalRule($approvalRuleId);
        }

        if ($context->getCustomer() instanceof CustomerEntity) {
            $factory->withCustomer($context->getCustomer()->getId());
        }

        return $factory->create($context->getContext())->getId();
    }

    private function getPendingOrderById(string $pendingOrderId, ?Context $context = null): PendingOrderEntity
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getPendingOrderRepository();

        $criteria = new Criteria([$pendingOrderId]);
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('approvalRule');
        $criteria->addAssociation('employee');
        $criteria->addAssociation('customer');
        $criteria->addAssociation('order');
        $criteria->addFilter(new EqualsFilter('stateMachineState.technicalName', PendingOrderStates::STATE_PENDING));

        $entity = $repository->search($criteria, $context)->first();

        if (! $entity instanceof PendingOrderEntity) {
            throw new \RuntimeException(sprintf('PendingOrder with ID "%s" not found', $pendingOrderId));
        }

        return $entity;
    }

    private function getPendingOrderByOrderId(string $orderId, ?Context $context = null): ?PendingOrderEntity
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getPendingOrderRepository();
        $criteria = new Criteria();
        $criteria->addAssociation('stateMachineState');
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->addFilter(new EqualsFilter('stateMachineState.technicalName', PendingOrderStates::STATE_PENDING));

        $entity = $repository->search($criteria, $context)->first();

        return $entity instanceof PendingOrderEntity ? $entity : null;
    }

    /**
     * @return array<int, PendingOrderEntity>
     */
    private function getPendingOrdersByEmployee(string $employeeId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getPendingOrderRepository();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('employeeId', $employeeId));

        /** @var array<string, PendingOrderEntity> $elements */
        $elements = $repository->search($criteria, $context)->getElements();

        return array_values($elements);
    }

    private function updatePendingOrderReason(string $pendingOrderId, string $reason, Context $context): void
    {
        $repository = $this->getPendingOrderRepository();

        $repository->update([
            [
                'id' => $pendingOrderId,
                'reason' => $reason,
            ],
        ], $context);
    }

    private function getOrderFromPendingOrder(string $pendingOrderId, Context $context): OrderEntity
    {
        $pendingOrder = $this->getPendingOrderById($pendingOrderId, $context);

        if (! $pendingOrder->getOrderId()) {
            throw new \RuntimeException('Pending order has not been converted to an order yet');
        }

        /** @var EntityRepository<OrderCollection> $orderRepository */
        $orderRepository = static::getContainer()->get('order.repository');

        $criteria = new Criteria([$pendingOrder->getOrderId()]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('stateMachineState');

        $order = $orderRepository->search($criteria, $context)->first();

        if (! $order instanceof OrderEntity) {
            throw new \RuntimeException(sprintf('Order with ID "%s" not found', $pendingOrder->getOrderId()));
        }

        return $order;
    }

    /**
     * @return EntityRepository<PendingOrderCollection>
     */
    private function getPendingOrderRepository(): EntityRepository
    {
        return static::getContainer()->get('b2b_components_pending_order.repository');
    }

    private function getB2bStateMachineRegistry(): StateMachineRegistry
    {
        return static::getContainer()->get(StateMachineRegistry::class);
    }
}
