<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tracks and simulates budget usage over time for testing.
 */
class BudgetUsageTracker
{
    private array $usageHistory = [];

    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Track a budget usage transaction.
     */
    public function trackUsage(string $budgetId, float $amount, string $description = '', ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createDefaultContext();
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

        // Track in history
        $this->usageHistory[$budgetId][] = [
            'amount' => $amount,
            'description' => $description,
            'timestamp' => new \DateTime(),
            'totalUsed' => $newUsedAmount,
        ];

        return $this->loadBudget($budgetId, $context);
    }

    /**
     * Simulate multiple usage transactions.
     */
    public function simulateUsage(string $budgetId, array $transactions, ?Context $context = null): BudgetEntity
    {
        foreach ($transactions as $transaction) {
            $amount = $transaction['amount'] ?? 0;
            $description = $transaction['description'] ?? '';
            $this->trackUsage($budgetId, $amount, $description, $context);
        }

        return $this->loadBudget($budgetId, $context);
    }

    /**
     * Get usage history for a budget.
     */
    public function getUsageHistory(string $budgetId): array
    {
        return $this->usageHistory[$budgetId] ?? [];
    }

    /**
     * Clear usage history.
     */
    public function clearHistory(?string $budgetId = null): void
    {
        if ($budgetId) {
            unset($this->usageHistory[$budgetId]);
        } else {
            $this->usageHistory = [];
        }
    }

    /**
     * Get total tracked usage for budget.
     */
    public function getTotalTrackedUsage(string $budgetId): float
    {
        $history = $this->getUsageHistory($budgetId);

        return array_sum(array_column($history, 'amount'));
    }

    /**
     * Simulate budget reaching threshold.
     */
    public function fillToPercentage(string $budgetId, float $percentage, ?Context $context = null): BudgetEntity
    {
        $budget = $this->loadBudget($budgetId, $context);
        $targetAmount = ($budget->getAmount() * $percentage) / 100;
        $remainingToAdd = $targetAmount - $budget->getUsedAmount();

        if ($remainingToAdd > 0) {
            $this->trackUsage($budgetId, $remainingToAdd, sprintf('Filled to %.2f%%', $percentage), $context);
        }

        return $this->loadBudget($budgetId, $context);
    }

    /**
     * Simulate budget exceeding limit.
     */
    public function exceedBudget(string $budgetId, float $excessAmount = 100.0, ?Context $context = null): BudgetEntity
    {
        $budget = $this->loadBudget($budgetId, $context);
        $targetAmount = $budget->getAmount() + $excessAmount;
        $remainingToAdd = $targetAmount - $budget->getUsedAmount();

        if ($remainingToAdd > 0) {
            $this->trackUsage($budgetId, $remainingToAdd, sprintf('Exceeded by %.2f', $excessAmount), $context);
        }

        return $this->loadBudget($budgetId, $context);
    }

    private function loadBudget(string $budgetId, Context $context): BudgetEntity
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $criteria = new Criteria([$budgetId]);
        $budget = $repository->search($criteria, $context)->first();

        if (! $budget) {
            throw new \RuntimeException(sprintf('Budget with ID "%s" not found', $budgetId));
        }

        return $budget;
    }
}
