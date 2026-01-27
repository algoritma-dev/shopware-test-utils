<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetCollection;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for testing budget notification triggers.
 * Verifies notification thresholds and sent status.
 */
class BudgetNotificationHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Check if budget should trigger notification.
     */
    public function shouldNotify(string $budgetId, ?Context $context = null): bool
    {
        $budget = $this->loadBudget($budgetId, $context);

        $notify = $budget->isNotify();

        if (! $notify) {
            return false;
        }

        $sent = $budget->isSent();

        if ($sent) {
            return false; // Already sent
        }

        $notificationConfig = $budget->getNotificationConfig();
        if (! $notificationConfig) {
            return false;
        }

        return $this->hasReachedThreshold($budget);
    }

    /**
     * Check if budget has reached notification threshold.
     */
    public function hasReachedThreshold(BudgetEntity $budget): bool
    {
        $notificationConfig = $budget->getNotificationConfig();
        if (! $notificationConfig) {
            return false;
        }

        $type = $notificationConfig['type'] ?? null;
        $value = $notificationConfig['value'] ?? null;

        if (! $type || $value === null) {
            return false;
        }

        if ($type === 'percentage') {
            $usagePercentage = ($budget->getUsedAmount() / $budget->getAmount()) * 100;

            return $usagePercentage >= (float) $value;
        }

        if ($type === 'amount') {
            return $budget->getUsedAmount() >= (float) $value;
        }

        return false;
    }

    /**
     * Mark notification as sent.
     */
    public function markAsSent(string $budgetId, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<BudgetCollection> $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $repository->update([
            [
                'id' => $budgetId,
                'sent' => true,
            ],
        ], $context);

        return $this->loadBudget($budgetId, $context);
    }

    /**
     * Reset notification sent status.
     */
    public function resetNotificationStatus(string $budgetId, ?Context $context = null): BudgetEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<BudgetCollection> $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $repository->update([
            [
                'id' => $budgetId,
                'sent' => false,
            ],
        ], $context);

        return $this->loadBudget($budgetId, $context);
    }

    /**
     * Get notification recipients for budget.
     *
     * @return array<int, array<string, string>>
     */
    public function getRecipients(string $budgetId, ?Context $context = null): array
    {
        $budget = $this->loadBudget($budgetId, $context);

        if (! $budget->getNotificationRecipients() instanceof EmployeeCollection) {
            return [];
        }

        $recipients = [];
        foreach ($budget->getNotificationRecipients() as $recipient) {
            // Assuming $recipient is EmployeeEntity or similar that has getEmployee() or is the employee itself?
            // The error says: Call to an undefined method Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity::getEmployee().
            // This suggests that $recipient IS an EmployeeEntity, so we don't need getEmployee().
            // Wait, if getNotificationRecipients returns EmployeeCollection, then $recipient is EmployeeEntity.
            // So we should use $recipient directly.

            $recipients[] = [
                'type' => 'employee',
                'id' => $recipient->getId(),
                'email' => $recipient->getEmail(),
            ];
        }

        return $recipients;
    }

    /**
     * Simulate notification trigger.
     *
     * @return array<string, mixed>
     */
    public function simulateNotificationTrigger(string $budgetId, ?Context $context = null): array
    {
        $budget = $this->loadBudget($budgetId, $context);

        if (! $this->shouldNotify($budgetId, $context)) {
            return [
                'triggered' => false,
                'reason' => 'Threshold not reached or notification already sent',
            ];
        }

        $recipients = $this->getRecipients($budgetId, $context);
        $this->markAsSent($budgetId, $context);

        return [
            'triggered' => true,
            'budget' => [
                'id' => $budget->getId(),
                'name' => $budget->getName(),
                'amount' => $budget->getAmount(),
                'usedAmount' => $budget->getUsedAmount(),
                'percentage' => ($budget->getUsedAmount() / $budget->getAmount()) * 100,
            ],
            'recipients' => $recipients,
            'config' => $budget->getNotificationConfig(),
        ];
    }

    /**
     * Test multiple notification scenarios.
     *
     * @param array<float> $usageAmounts
     *
     * @return array<int, array<string, mixed>>
     */
    public function testNotificationScenarios(string $budgetId, array $usageAmounts, ?Context $context = null): array
    {
        $results = [];

        foreach ($usageAmounts as $amount) {
            // Update budget usage
            $this->updateBudgetUsage($budgetId, $amount, $context);

            $results[] = [
                'usedAmount' => $amount,
                'shouldNotify' => $this->shouldNotify($budgetId, $context),
                'hasReachedThreshold' => $this->hasReachedThreshold($this->loadBudget($budgetId, $context)),
            ];
        }

        return $results;
    }

    private function updateBudgetUsage(string $budgetId, float $usedAmount, Context $context): void
    {
        /** @var EntityRepository<BudgetCollection> $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $repository->update([
            [
                'id' => $budgetId,
                'usedAmount' => $usedAmount,
                'sent' => false, // Reset for testing
            ],
        ], $context);
    }

    private function loadBudget(string $budgetId, ?Context $context): BudgetEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<BudgetCollection> $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $criteria = new Criteria([$budgetId]);
        $criteria->addAssociation('notificationRecipients');
        $criteria->addAssociation('notificationRecipients.employee');

        /** @var BudgetEntity|null $budget */
        $budget = $repository->search($criteria, $context)->first();

        if (! $budget) {
            throw new \RuntimeException(sprintf('Budget with ID "%s" not found', $budgetId));
        }

        return $budget;
    }
}
