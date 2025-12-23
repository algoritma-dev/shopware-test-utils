<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for testing budget renewal logic.
 * Simulates automatic budget resets based on renewal type.
 */
class BudgetRenewHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Manually trigger budget renewal.
     */
    public function renewBudget(string $budgetId, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createDefaultContext();
        $this->loadBudget($budgetId, $context);

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $repository->update([
            [
                'id' => $budgetId,
                'usedAmount' => 0.0,
                'lastRenews' => new \DateTime(),
            ],
        ], $context);

        return $this->loadBudget($budgetId, $context);
    }

    /**
     * Check if budget should be renewed.
     */
    public function shouldRenew(string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->loadBudget($budgetId, $context);

        if ($budget->getRenewsType() === 'none') {
            return false;
        }

        $lastRenews = $budget->getLastRenews();
        if (! $lastRenews) {
            return true;
        }

        $now = new \DateTime();

        return match ($budget->getRenewsType()) {
            'daily' => $lastRenews->format('Y-m-d') !== $now->format('Y-m-d'),
            'weekly' => $lastRenews->format('Y-W') !== $now->format('Y-W'),
            'monthly' => $lastRenews->format('Y-m') !== $now->format('Y-m'),
            'yearly' => $lastRenews->format('Y') !== $now->format('Y'),
            default => false,
        };
    }

    /**
     * Get next renewal date for budget.
     */
    public function getNextRenewalDate(string $budgetId, ?Context $context = null): ?\DateTime
    {
        $budget = $this->loadBudget($budgetId, $context);

        if ($budget->getRenewsType() === 'none') {
            return null;
        }

        $lastRenews = $budget->getLastRenews() ?? new \DateTime();

        return match ($budget->getRenewsType()) {
            'daily' => (clone $lastRenews)->modify('+1 day'),
            'weekly' => (clone $lastRenews)->modify('+1 week'),
            'monthly' => (clone $lastRenews)->modify('+1 month'),
            'yearly' => (clone $lastRenews)->modify('+1 year'),
            default => null,
        };
    }

    /**
     * Simulate time passing and renew if needed.
     */
    public function simulateTimePassage(string $budgetId, \DateTime $targetDate, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createDefaultContext();
        $budget = $this->loadBudget($budgetId, $context);

        $lastRenews = $budget->getLastRenews() ?? new \DateTime();
        $currentDate = clone $lastRenews;

        while ($currentDate < $targetDate) {
            $nextRenewal = $this->calculateNextRenewalFrom($currentDate, $budget->getRenewsType());

            if ($nextRenewal <= $targetDate) {
                $this->renewBudget($budgetId, $context);
                $currentDate = $nextRenewal;
            } else {
                break;
            }
        }

        return $this->loadBudget($budgetId, $context);
    }

    /**
     * Test renewal for different periods.
     */
    public function testRenewalCycle(string $budgetId, int $cycles, ?Context $context = null): array
    {
        $budget = $this->loadBudget($budgetId, $context);
        $results = [];

        for ($i = 0; $i < $cycles; ++$i) {
            $budget = $this->renewBudget($budgetId, $context);
            $results[] = [
                'cycle' => $i + 1,
                'renewedAt' => $budget->getLastRenews(),
                'usedAmount' => $budget->getUsedAmount(),
            ];
        }

        return $results;
    }

    private function calculateNextRenewalFrom(\DateTime $date, string $renewsType): \DateTime
    {
        return match ($renewsType) {
            'daily' => (clone $date)->modify('+1 day'),
            'weekly' => (clone $date)->modify('+1 week'),
            'monthly' => (clone $date)->modify('+1 month'),
            'yearly' => (clone $date)->modify('+1 year'),
            default => clone $date,
        };
    }

    private function loadBudget(string $budgetId, ?Context $context): BudgetEntity
    {
        $context ??= Context::createDefaultContext();

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
