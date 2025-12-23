<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteStates;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * B2B-specific assertions for testing.
 * Provides high-level assertions for quotes, budgets, approvals, permissions, etc.
 */
trait B2BAssertions
{
    /**
     * Assert quote is in a specific state.
     */
    protected function assertQuoteInState(string $quoteId, string $expectedState, ?Context $context = null): void
    {
        $quote = $this->loadQuoteEntity($quoteId, $context);
        $actualState = $quote->getStateMachineState()->getTechnicalName();

        static::assertEquals(
            $expectedState,
            $actualState,
            sprintf('Expected quote to be in state "%s", but got "%s"', $expectedState, $actualState)
        );
    }

    /**
     * Assert budget is exceeded.
     */
    protected function assertBudgetExceeded(string $budgetId, ?Context $context = null): void
    {
        $budget = $this->loadBudgetEntity($budgetId, $context);
        $remaining = $budget->getAmount() - $budget->getUsedAmount();

        static::assertLessThan(
            0,
            $remaining,
            sprintf('Expected budget to be exceeded, but remaining budget is %.2f', $remaining)
        );
    }

    /**
     * Assert budget is not exceeded.
     */
    protected function assertBudgetNotExceeded(string $budgetId, ?Context $context = null): void
    {
        $budget = $this->loadBudgetEntity($budgetId, $context);
        $remaining = $budget->getAmount() - $budget->getUsedAmount();

        static::assertGreaterThanOrEqual(
            0,
            $remaining,
            sprintf('Expected budget not to be exceeded, but it is over by %.2f', abs($remaining))
        );
    }

    /**
     * Assert employee has a specific permission.
     */
    protected function assertEmployeeHasPermission(string $employeeId, string $permissionCode, ?Context $context = null): void
    {
        $employee = $this->loadEmployeeEntity($employeeId, $context);
        $role = $employee->getRole();

        if (! $role) {
            static::fail(sprintf('Employee "%s" has no role assigned', $employeeId));
        }

        $permissions = $role->getPermissions();
        if (! $permissions) {
            static::fail(sprintf('Role "%s" has no permissions', $role->getId()));
        }

        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($permission === $permissionCode) {
                $hasPermission = true;
                break;
            }
        }

        static::assertTrue(
            $hasPermission,
            sprintf('Expected employee to have permission "%s", but it was not found', $permissionCode)
        );
    }

    /**
     * Assert order needs approval.
     */
    protected function assertOrderNeedsApproval(string $orderId, ?Context $context = null): void
    {
        $pendingOrder = $this->loadPendingOrderByOrderId($orderId, $context);

        static::assertNotNull(
            $pendingOrder,
            sprintf('Expected order "%s" to require approval, but no pending order found', $orderId)
        );
    }

    /**
     * Assert pending order was created.
     */
    protected function assertPendingOrderCreated(string $employeeId, ?Context $context = null): void
    {
        $pendingOrders = $this->loadPendingOrdersForEmployee($employeeId, $context);

        static::assertNotEmpty(
            $pendingOrders,
            sprintf('Expected pending order to be created for employee "%s", but none found', $employeeId)
        );
    }

    /**
     * Assert pending order is in specific state.
     */
    protected function assertPendingOrderInState(string $pendingOrderId, string $expectedState, ?Context $context = null): void
    {
        $pendingOrder = $this->loadPendingOrderEntity($pendingOrderId, $context);
        $actualState = $pendingOrder->getStateMachineState()->getTechnicalName();

        static::assertEquals(
            $expectedState,
            $actualState,
            sprintf('Expected pending order to be in state "%s", but got "%s"', $expectedState, $actualState)
        );
    }

    /**
     * Assert quote has comments.
     */
    protected function assertQuoteHasComments(string $quoteId, ?int $expectedCount = null, ?Context $context = null): void
    {
        $comments = $this->loadQuoteComments($quoteId, $context);

        if ($expectedCount !== null) {
            static::assertCount(
                $expectedCount,
                $comments,
                sprintf('Expected quote to have %d comments, but has %d', $expectedCount, count($comments))
            );
        } else {
            static::assertNotEmpty(
                $comments,
                sprintf('Expected quote "%s" to have comments, but none found', $quoteId)
            );
        }
    }

    /**
     * Assert budget notification should be triggered.
     */
    protected function assertBudgetNotificationTriggered(string $budgetId, ?Context $context = null): void
    {
        $budget = $this->loadBudgetEntity($budgetId, $context);

        $notify = $budget->isNotify();

        static::assertTrue(
            $notify,
            'Budget notification is not enabled'
        );

        $usagePercentage = ($budget->getUsedAmount() / $budget->getAmount()) * 100;
        $notificationConfig = $budget->getNotificationConfig();

        if (! $notificationConfig) {
            static::fail('Budget has no notification configuration');
        }

        $threshold = (float) ($notificationConfig['value'] ?? 0);

        if ($notificationConfig['type'] === 'percentage') {
            static::assertGreaterThanOrEqual(
                $threshold,
                $usagePercentage,
                sprintf('Expected budget to reach notification threshold of %.2f%%, but usage is only %.2f%%', $threshold, $usagePercentage)
            );
        }
    }

    /**
     * Assert employee has role.
     */
    protected function assertEmployeeHasRole(string $employeeId, string $roleId, ?Context $context = null): void
    {
        $employee = $this->loadEmployeeEntity($employeeId, $context);

        static::assertEquals(
            $roleId,
            $employee->getRoleId(),
            sprintf('Expected employee to have role "%s", but has "%s"', $roleId, $employee->getRoleId())
        );
    }

    /**
     * Assert quote can be converted to order.
     */
    protected function assertQuoteCanBeConverted(string $quoteId, ?Context $context = null): void
    {
        $quote = $this->loadQuoteEntity($quoteId, $context);

        static::assertNotNull(
            $quote->getLineItems(),
            'Quote has no line items'
        );

        static::assertNotEmpty(
            $quote->getLineItems(),
            'Quote has empty line items'
        );

        $state = $quote->getStateMachineState()->getTechnicalName();
        static::assertEquals(
            QuoteStates::STATE_ACCEPTED,
            $state,
            sprintf('Quote must be in accepted state to be converted, but is in state "%s"', $state)
        );
    }

    // Private helper methods to load entities

    private function loadQuoteEntity(string $quoteId, ?Context $context): QuoteEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('quote.repository');
        $criteria = new Criteria([$quoteId]);
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('lineItems');

        $quote = $repository->search($criteria, $context)->first();
        if (! $quote) {
            static::fail(sprintf('Quote with ID "%s" not found', $quoteId));
        }

        return $quote;
    }

    private function loadBudgetEntity(string $budgetId, ?Context $context): BudgetEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('b2b_components_budget.repository');
        $criteria = new Criteria([$budgetId]);

        $budget = $repository->search($criteria, $context)->first();
        if (! $budget) {
            static::fail(sprintf('Budget with ID "%s" not found', $budgetId));
        }

        return $budget;
    }

    private function loadEmployeeEntity(string $employeeId, ?Context $context): EmployeeEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('b2b_employee.repository');
        $criteria = new Criteria([$employeeId]);
        $criteria->addAssociation('role');
        $criteria->addAssociation('role.permissions');

        $employee = $repository->search($criteria, $context)->first();
        if (! $employee) {
            static::fail(sprintf('Employee with ID "%s" not found', $employeeId));
        }

        return $employee;
    }

    private function loadPendingOrderEntity(string $pendingOrderId, ?Context $context): PendingOrderEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('b2b_components_pending_order.repository');
        $criteria = new Criteria([$pendingOrderId]);
        $criteria->addAssociation('stateMachineState');

        $pendingOrder = $repository->search($criteria, $context)->first();
        if (! $pendingOrder) {
            static::fail(sprintf('PendingOrder with ID "%s" not found', $pendingOrderId));
        }

        return $pendingOrder;
    }

    private function loadPendingOrderByOrderId(string $orderId, ?Context $context): ?PendingOrderEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('b2b_components_pending_order.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));

        return $repository->search($criteria, $context)->first();
    }

    private function loadPendingOrdersForEmployee(string $employeeId, ?Context $context): array
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('b2b_components_pending_order.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('employeeId', $employeeId));

        return array_values($repository->search($criteria, $context)->getElements());
    }

    private function loadShoppingListEntity(string $shoppingListId, ?Context $context): ShoppingListEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('shopping_list.repository');
        $criteria = new Criteria([$shoppingListId]);
        $criteria->addAssociation('sharedWith');

        $shoppingList = $repository->search($criteria, $context)->first();
        if (! $shoppingList) {
            static::fail(sprintf('ShoppingList with ID "%s" not found', $shoppingListId));
        }

        return $shoppingList;
    }

    private function loadQuoteComments(string $quoteId, ?Context $context): array
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('quote_comment.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('quoteId', $quoteId));

        return array_values($repository->search($criteria, $context)->getElements());
    }
}
