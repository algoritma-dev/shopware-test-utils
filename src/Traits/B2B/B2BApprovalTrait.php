<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\ApprovalWorkflowHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\OrderApprovalHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\OrderApprovalRequestHelper;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait B2BApprovalTrait
{
    use KernelTestBehaviour;

    private ?ApprovalWorkflowHelper $b2bApprovalWorkflowHelperInstance = null;

    private ?OrderApprovalHelper $b2bOrderApprovalHelperInstance = null;

    private ?OrderApprovalRequestHelper $b2bOrderApprovalRequestHelperInstance = null;

    protected function getB2bApprovalWorkflowHelper(): ApprovalWorkflowHelper
    {
        if (! $this->b2bApprovalWorkflowHelperInstance instanceof ApprovalWorkflowHelper) {
            $this->b2bApprovalWorkflowHelperInstance = new ApprovalWorkflowHelper(static::getContainer());
        }

        return $this->b2bApprovalWorkflowHelperInstance;
    }

    protected function getB2bOrderApprovalHelper(): OrderApprovalHelper
    {
        if (! $this->b2bOrderApprovalHelperInstance instanceof OrderApprovalHelper) {
            $this->b2bOrderApprovalHelperInstance = new OrderApprovalHelper(static::getContainer());
        }

        return $this->b2bOrderApprovalHelperInstance;
    }

    protected function getB2bOrderApprovalRequestHelper(): OrderApprovalRequestHelper
    {
        if (! $this->b2bOrderApprovalRequestHelperInstance instanceof OrderApprovalRequestHelper) {
            $this->b2bOrderApprovalRequestHelperInstance = new OrderApprovalRequestHelper(static::getContainer());
        }

        return $this->b2bOrderApprovalRequestHelperInstance;
    }

    protected function b2bApprovalApprovePendingOrder(string $pendingOrderId, ?Context $context = null): PendingOrderEntity
    {
        return $this->getB2bApprovalWorkflowHelper()->approvePendingOrder($pendingOrderId, $context);
    }

    protected function b2bApprovalDeclinePendingOrder(
        string $pendingOrderId,
        ?string $reason = null,
        ?Context $context = null
    ): PendingOrderEntity {
        return $this->getB2bApprovalWorkflowHelper()->declinePendingOrder($pendingOrderId, $reason, $context);
    }

    protected function b2bApprovalConvertPendingOrderToOrder(string $pendingOrderId, ?Context $context = null): OrderEntity
    {
        return $this->getB2bApprovalWorkflowHelper()->convertToOrder($pendingOrderId, $context);
    }

    protected function b2bApprovalSimulateFullWorkflow(string $pendingOrderId, ?Context $context = null): OrderEntity
    {
        return $this->getB2bApprovalWorkflowHelper()->simulateFullApprovalWorkflow($pendingOrderId, $context);
    }

    protected function b2bApprovalSimulateRejectionWorkflow(
        string $pendingOrderId,
        string $reason = 'Budget exceeded',
        ?Context $context = null
    ): PendingOrderEntity {
        return $this->getB2bApprovalWorkflowHelper()->simulateRejectionWorkflow($pendingOrderId, $reason, $context);
    }

    /**
     * @return array<PendingOrderEntity>
     */
    protected function b2bApprovalGetPendingOrdersForEmployee(string $employeeId, ?Context $context = null): array
    {
        return $this->getB2bApprovalWorkflowHelper()->getPendingOrdersForEmployee($employeeId, $context);
    }

    /**
     * @return array<PendingOrderEntity>
     */
    protected function b2bApprovalGetAllPendingApprovals(?Context $context = null): array
    {
        return $this->getB2bApprovalWorkflowHelper()->getAllPendingApprovals($context);
    }

    protected function b2bApprovalCanApprove(string $pendingOrderId, ?Context $context = null): bool
    {
        return $this->getB2bApprovalWorkflowHelper()->canApprove($pendingOrderId, $context);
    }

    protected function b2bApprovalGetCurrentState(string $pendingOrderId, ?Context $context = null): string
    {
        return $this->getB2bApprovalWorkflowHelper()->getCurrentState($pendingOrderId, $context);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function b2bOrderApprovalRequestPendingOrder(
        SalesChannelContext $context,
        CustomerEntity $customer,
        array $data = []
    ): PendingOrderEntity {
        return $this->getB2bOrderApprovalHelper()->requestPendingOrder($context, $customer, $data);
    }

    protected function b2bOrderApprovalAssertOrderNeedsApproval(string $orderId, ?Context $context = null): void
    {
        $this->getB2bOrderApprovalHelper()->assertOrderNeedsApproval($orderId, $context);
    }

    protected function b2bOrderApprovalAssertPendingOrderCreated(string $employeeId, ?Context $context = null): void
    {
        $this->getB2bOrderApprovalHelper()->assertPendingOrderCreated($employeeId, $context);
    }

    protected function b2bOrderApprovalAssertPendingOrderInState(
        string $pendingOrderId,
        string $expectedState,
        ?Context $context = null
    ): void {
        $this->getB2bOrderApprovalHelper()->assertPendingOrderInState($pendingOrderId, $expectedState, $context);
    }

    protected function b2bOrderApprovalRequest(
        Cart $cart,
        SalesChannelContext $context,
        string $employeeId,
        ?string $approvalRuleId = null
    ): string {
        return $this->getB2bOrderApprovalRequestHelper()->requestApproval(
            $cart,
            $context,
            $employeeId,
            $approvalRuleId
        );
    }

    protected function b2bOrderApprovalRequestWithReason(
        Cart $cart,
        SalesChannelContext $context,
        string $employeeId,
        string $reason,
        ?string $approvalRuleId = null
    ): string {
        return $this->getB2bOrderApprovalRequestHelper()->requestApprovalWithReason(
            $cart,
            $context,
            $employeeId,
            $reason,
            $approvalRuleId
        );
    }
}
