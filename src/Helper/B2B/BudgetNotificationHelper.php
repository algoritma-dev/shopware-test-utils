<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
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

        if (! $budget->getNotify()) {
            return false;
        }

        if ($budget->getSent()) {
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
        $context ??= Context::createDefaultContext();

        /** @var EntityRepository $repository */
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
        $context ??= Context::createDefaultContext();

        /** @var EntityRepository $repository */
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
     */
    public function getRecipients(string $budgetId, ?Context $context = null): array
    {
        $budget = $this->loadBudget($budgetId, $context);

        if (! $budget->getNotificationRecipients()) {
            return [];
        }

        $recipients = [];
        foreach ($budget->getNotificationRecipients() as $recipient) {
            if ($recipient->getEmployee()) {
                $recipients[] = [
                    'type' => 'employee',
                    'id' => $recipient->getEmployee()->getId(),
                    'email' => $recipient->getEmployee()->getEmail(),
                ];
            }
        }

        return $recipients;
    }

    /**
     * Simulate notification trigger.
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
        /** @var EntityRepository $repository */
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
        $context ??= Context::createDefaultContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_components_budget.repository');

        $criteria = new Criteria([$budgetId]);
        $criteria->addAssociation('notificationRecipients');
        $criteria->addAssociation('notificationRecipients.employee');

        $budget = $repository->search($criteria, $context)->first();

        if (! $budget) {
            throw new \RuntimeException(sprintf('Budget with ID "%s" not found', $budgetId));
        }

        return $budget;
    }
}
