<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetCollection;
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
        $context ??= Context::createCLIContext();
        $this->loadBudget($budgetId, $context);

        /** @var EntityRepository<BudgetCollection> $repository */
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

        $renewsType = $this->getRenewsTypeValue($budget);

        if ($renewsType === 'none') {
            return false;
        }

        $lastRenews = $budget->getLastRenews();

        $now = new \DateTime();

        return match (strtolower($renewsType)) {
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
    public function getNextRenewalDate(string $budgetId, ?Context $context = null): ?\DateTimeInterface
    {
        $budget = $this->loadBudget($budgetId, $context);
        $renewsType = $this->getRenewsTypeValue($budget);

        if ($renewsType === 'none') {
            return null;
        }

        $lastRenews = $budget->getLastRenews();
        $baseDate = \DateTimeImmutable::createFromInterface($lastRenews);

        return match (strtolower($renewsType)) {
            'daily' => $baseDate->modify('+1 day'),
            'weekly' => $baseDate->modify('+1 week'),
            'monthly' => $baseDate->modify('+1 month'),
            'yearly' => $baseDate->modify('+1 year'),
            default => null,
        };
    }

    /**
     * Simulate time passing and renew if needed.
     */
    public function simulateTimePassage(string $budgetId, \DateTimeInterface $targetDate, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createCLIContext();
        $budget = $this->loadBudget($budgetId, $context);

        $lastRenews = $budget->getLastRenews();
        $currentDate = \DateTimeImmutable::createFromInterface($lastRenews);
        $targetDate = \DateTimeImmutable::createFromInterface($targetDate);
        $renewsType = $this->getRenewsTypeValue($budget);

        if ($renewsType === 'none') {
            return $budget;
        }

        while ($currentDate < $targetDate) {
            $nextRenewal = $this->calculateNextRenewalFrom($currentDate, $renewsType);

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
     *
     * @return array<int, array<string, mixed>>
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

    private function calculateNextRenewalFrom(\DateTimeImmutable $date, string $renewsType): \DateTimeImmutable
    {
        $baseDate = \DateTimeImmutable::createFromInterface($date);

        return match (strtolower($renewsType)) {
            'daily' => $baseDate->modify('+1 day'),
            'weekly' => $baseDate->modify('+1 week'),
            'monthly' => $baseDate->modify('+1 month'),
            'yearly' => $baseDate->modify('+1 year'),
            default => $baseDate,
        };
    }

    private function loadBudget(string $budgetId, ?Context $context): BudgetEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<BudgetCollection> $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $criteria = new Criteria([$budgetId]);

        /** @var BudgetEntity|null $budget */
        $budget = $repository->search($criteria, $context)->first();

        if (! $budget) {
            throw new \RuntimeException(sprintf('Budget with ID "%s" not found', $budgetId));
        }

        return $budget;
    }

    private function getRenewsTypeValue(BudgetEntity $budget): string
    {
        $type = $budget->getRenewsType();

        return $type->value;
    }
}
