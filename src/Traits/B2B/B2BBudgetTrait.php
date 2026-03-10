<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetCollection;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeCollection;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * Trait for B2B budget management operations and assertions.
 */
trait B2BBudgetTrait
{
    use KernelTestBehaviour;

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    private array $budgetUsageHistory = [];

    protected function renewBudget(string $budgetId, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createCLIContext();
        
        $repository = $this->getBudgetRepository();

        $repository->update([
            [
                'id' => $budgetId,
                'usedAmount' => 0.0,
                'lastRenews' => new \DateTime(),
            ],
        ], $context);

        return $this->getBudgetById($budgetId, $context);
    }

    protected function shouldBudgetRenew(string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->getBudgetById($budgetId, $context);
        $renewsType = $budget->getRenewsType()->value;

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

    protected function getNextBudgetRenewalDate(string $budgetId, ?Context $context = null): ?\DateTimeInterface
    {
        $budget = $this->getBudgetById($budgetId, $context);
        $renewsType = $budget->getRenewsType()->value;

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

    protected function simulateBudgetUsage(string $budgetId, float $amount, string $description = '', ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createCLIContext();
        $budget = $this->getBudgetById($budgetId, $context);

        $newUsedAmount = $budget->getUsedAmount() + $amount;
        $repository = $this->getBudgetRepository();

        $repository->update([
            [
                'id' => $budgetId,
                'usedAmount' => $newUsedAmount,
            ],
        ], $context);

        // Track in history
        $this->budgetUsageHistory[$budgetId][] = [
            'amount' => $amount,
            'description' => $description,
            'timestamp' => new \DateTime(),
            'totalUsed' => $newUsedAmount,
        ];

        return $this->getBudgetById($budgetId, $context);
    }

    /**
     * @param array<int, array<string, mixed>> $transactions
     */
    protected function simulateMultipleBudgetUsages(string $budgetId, array $transactions, ?Context $context = null): BudgetEntity
    {
        foreach ($transactions as $transaction) {
            $amount = isset($transaction['amount']) ? (float) $transaction['amount'] : 0.0;
            $description = isset($transaction['description']) ? (string) $transaction['description'] : '';
            $this->simulateBudgetUsage($budgetId, $amount, $description, $context);
        }

        return $this->getBudgetById($budgetId, $context);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getBudgetUsageHistory(string $budgetId): array
    {
        return $this->budgetUsageHistory[$budgetId] ?? [];
    }

    protected function clearBudgetUsageHistory(?string $budgetId = null): void
    {
        if ($budgetId) {
            unset($this->budgetUsageHistory[$budgetId]);
        } else {
            $this->budgetUsageHistory = [];
        }
    }

    protected function fillBudgetToPercentage(string $budgetId, float $percentage, ?Context $context = null): BudgetEntity
    {
        $budget = $this->getBudgetById($budgetId, $context);
        $targetAmount = ($budget->getAmount() * $percentage) / 100;
        $remainingToAdd = $targetAmount - $budget->getUsedAmount();

        if ($remainingToAdd > 0) {
            $this->simulateBudgetUsage($budgetId, $remainingToAdd, sprintf('Filled to %.2f%%', $percentage), $context);
        }

        return $this->getBudgetById($budgetId, $context);
    }

    protected function exceedBudget(string $budgetId, float $excessAmount = 100.0, ?Context $context = null): BudgetEntity
    {
        $budget = $this->getBudgetById($budgetId, $context);
        $targetAmount = $budget->getAmount() + $excessAmount;
        $remainingToAdd = $targetAmount - $budget->getUsedAmount();

        if ($remainingToAdd > 0) {
            $this->simulateBudgetUsage($budgetId, $remainingToAdd, sprintf('Exceeded by %.2f', $excessAmount), $context);
        }

        return $this->getBudgetById($budgetId, $context);
    }

    protected function budgetExceedsAmount(float $amount, string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->getBudgetById($budgetId, $context);
        $remainingBudget = $budget->getAmount() - $budget->getUsedAmount();

        return $amount > $remainingBudget;
    }

    protected function isBudgetActive(string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->getBudgetById($budgetId, $context);

        if (! $budget->getActive()) {
            return false;
        }

        $now = new \DateTime();
        $startDate = $budget->getStartDate();
        $endDate = $budget->getEndDate();

        if ($startDate > $now) {
            return false;
        }

        return ! ($endDate instanceof \DateTimeInterface && $endDate < $now);
    }

    protected function budgetRequiresApproval(float $amount, string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->getBudgetById($budgetId, $context);

        if ($budget->getAllowApproval()) {
            return $amount > ($budget->getAmount() - $budget->getUsedAmount());
        }

        return false;
    }

    protected function resetBudgetUsage(string $budgetId, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getBudgetRepository();

        $repository->update([
            [
                'id' => $budgetId,
                'usedAmount' => 0.0,
            ],
        ], $context);

        return $this->getBudgetById($budgetId, $context);
    }

    /**
     * @return array<BudgetEntity>
     */
    protected function getActiveBudgetsForOrganization(string $organizationId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getBudgetRepository();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('organizations.id', $organizationId));
        $criteria->addAssociation('organizations');

        /** @var array<string, BudgetEntity> $elements */
        $elements = $repository->search($criteria, $context)->getElements();

        return array_values($elements);
    }

    protected function shouldBudgetTriggerNotification(string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->getBudgetById($budgetId, $context);

        if (! $budget->isNotify() || $budget->isSent()) {
            return false;
        }

        $notificationConfig = $budget->getNotificationConfig();
        if (! $notificationConfig) {
            return false;
        }

        $type = $notificationConfig['type'] ?? null;
        $value = $notificationConfig['value'] ?? null;

        if (! $type || $value === null) {
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

    protected function getBudgetNotificationRecipients(string $budgetId, ?Context $context = null): array
    {
        $budget = $this->getBudgetById($budgetId, $context);

        if (! $budget->getNotificationRecipients() instanceof EmployeeCollection) {
            return [];
        }

        $recipients = [];
        foreach ($budget->getNotificationRecipients() as $recipient) {
            $recipients[] = [
                'type' => 'employee',
                'id' => $recipient->getId(),
                'email' => $recipient->getEmail(),
            ];
        }

        return $recipients;
    }

    protected function markBudgetNotificationAsSent(string $budgetId, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createCLIContext();
        $this->getBudgetRepository()->update([['id' => $budgetId, 'sent' => true]], $context);

        return $this->getBudgetById($budgetId, $context);
    }

    protected function assertBudgetExceeded(string $budgetId, ?Context $context = null): void
    {
        $budget = $this->getBudgetById($budgetId, $context);
        $remaining = $budget->getAmount() - $budget->getUsedAmount();

        assert(
            $remaining < 0,
            sprintf('Expected budget to be exceeded, but remaining budget is %.2f', $remaining)
        );
    }

    protected function assertBudgetNotExceeded(string $budgetId, ?Context $context = null): void
    {
        $budget = $this->getBudgetById($budgetId, $context);
        $remaining = $budget->getAmount() - $budget->getUsedAmount();

        assert(
            $remaining >= 0,
            sprintf('Expected budget not to be exceeded, but it is over by %.2f', abs($remaining))
        );
    }

    protected function assertBudgetNotificationTriggered(string $budgetId, ?Context $context = null): void
    {
        $budget = $this->getBudgetById($budgetId, $context);
        assert($budget->isNotify(), 'Budget notification is not enabled');

        $notificationConfig = $budget->getNotificationConfig();
        if (! $notificationConfig) {
            throw new \RuntimeException('Budget has no notification configuration');
        }

        assert(
            $this->shouldBudgetTriggerNotification($budgetId, $context),
            'Expected budget notification to be triggered'
        );
    }

    private function getBudgetById(string $budgetId, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getBudgetRepository();

        $criteria = new Criteria([$budgetId]);
        $criteria->addAssociation('organizations');
        $criteria->addAssociation('employees');
        $criteria->addAssociation('roles');
        $criteria->addAssociation('notificationRecipients');
        $criteria->addAssociation('notificationRecipients.employee');

        $budget = $repository->search($criteria, $context)->first();

        if (! $budget instanceof BudgetEntity) {
            throw new \RuntimeException(sprintf('Budget with ID "%s" not found', $budgetId));
        }

        return $budget;
    }

    /**
     * @return EntityRepository<BudgetCollection>
     */
    private function getBudgetRepository(): EntityRepository
    {
        return static::getContainer()->get('b2b_components_budget.repository');
    }
}
