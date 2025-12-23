<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for testing budget validations in approval workflows.
 * Checks budget limits, usage, and renewal logic.
 */
class BudgetValidationHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Check if cart amount exceeds budget.
     */
    public function exceedsBudget(Cart $cart, string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->loadBudget($budgetId, $context);
        $cartTotal = $cart->getPrice()->getTotalPrice();

        return $this->exceedsBudgetAmount($cartTotal, $budget);
    }

    /**
     * Check if an amount exceeds budget.
     */
    public function exceedsBudgetAmount(float $amount, BudgetEntity $budget): bool
    {
        $remainingBudget = $this->getRemainingBudget($budget);

        return $amount > $remainingBudget;
    }

    /**
     * Get remaining budget amount.
     */
    public function getRemainingBudget(BudgetEntity $budget): float
    {
        return $budget->getAmount() - $budget->getUsedAmount();
    }

    /**
     * Check if budget is active and valid.
     */
    public function isBudgetActive(string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->loadBudget($budgetId, $context);

        if (! $budget->getActive()) {
            return false;
        }

        // Check date range
        $now = new \DateTime();
        $startDate = $budget->getStartDate();
        $endDate = $budget->getEndDate();

        if ($startDate && $startDate > $now) {
            return false;
        }

        return ! ($endDate && $endDate < $now);
    }

    /**
     * Check if budget requires approval for given amount.
     */
    public function requiresApproval(float $amount, string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->loadBudget($budgetId, $context);

        // If budget has allowApproval enabled and amount exceeds budget
        if ($budget->getAllowApproval()) {
            return $this->exceedsBudgetAmount($amount, $budget);
        }

        return false;
    }

    /**
     * Simulate budget usage.
     */
    public function simulateBudgetUsage(string $budgetId, float $amount, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createCLIContext();

        $budget = $this->loadBudget($budgetId, $context);
        $newUsedAmount = $budget->getUsedAmount() + $amount;

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $repository->update([
            [
                'id' => $budgetId,
                'usedAmount' => $newUsedAmount,
            ],
        ], $context);

        return $this->loadBudget($budgetId, $context);
    }

    /**
     * Reset budget usage to 0.
     */
    public function resetBudgetUsage(string $budgetId, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $repository->update([
            [
                'id' => $budgetId,
                'usedAmount' => 0.0,
            ],
        ], $context);

        return $this->loadBudget($budgetId, $context);
    }

    /**
     * Get all active budgets for an organization.
     */
    public function getActiveBudgetsForOrganization(string $organizationId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('organizations.id', $organizationId));
        $criteria->addAssociation('organizations');

        $result = $repository->search($criteria, $context);

        return array_values($result->getElements());
    }

    /**
     * Check if budget should trigger notification.
     */
    public function shouldNotify(string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->loadBudget($budgetId, $context);

        if (! $budget->getNotify()) {
            return false;
        }

        $notificationConfig = $budget->getNotificationConfig();
        if (! $notificationConfig) {
            return false;
        }

        $type = $notificationConfig['type'] ?? null;
        $value = $notificationConfig['value'] ?? null;

        if (! $type || ! $value) {
            return false;
        }

        $usagePercentage = ($budget->getUsedAmount() / $budget->getAmount()) * 100;

        if ($type === 'percentage') {
            return $usagePercentage >= (float) $value;
        }

        if ($type === 'amount') {
            return $budget->getUsedAmount() >= (float) $value;
        }

        return false;
    }

    /**
     * Get budget usage percentage.
     */
    public function getUsagePercentage(string $budgetId, ?Context $context = null): float
    {
        $budget = $this->loadBudget($budgetId, $context);

        if ($budget->getAmount() === 0.0) {
            return 0.0;
        }

        return ($budget->getUsedAmount() / $budget->getAmount()) * 100;
    }

    private function loadBudget(string $budgetId, ?Context $context): BudgetEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $criteria = new Criteria([$budgetId]);
        $criteria->addAssociation('organizations');
        $criteria->addAssociation('employees');
        $criteria->addAssociation('roles');

        $budget = $repository->search($criteria, $context)->first();

        if (! $budget) {
            throw new \RuntimeException(sprintf('Budget with ID "%s" not found', $budgetId));
        }

        return $budget;
    }
}
