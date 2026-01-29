<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\BudgetNotificationHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\BudgetRenewHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\BudgetUsageTracker;
use Algoritma\ShopwareTestUtils\Helper\B2B\BudgetValidationHelper;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

trait B2BBudgetTrait
{
    use KernelTestBehaviour;

    private ?BudgetUsageTracker $b2bBudgetUsageTrackerInstance = null;

    private ?BudgetRenewHelper $b2bBudgetRenewHelperInstance = null;

    private ?BudgetNotificationHelper $b2bBudgetNotificationHelperInstance = null;

    private ?BudgetValidationHelper $b2bBudgetValidationHelperInstance = null;

    protected function getB2bBudgetUsageTracker(): BudgetUsageTracker
    {
        if (! $this->b2bBudgetUsageTrackerInstance instanceof BudgetUsageTracker) {
            $this->b2bBudgetUsageTrackerInstance = new BudgetUsageTracker(static::getContainer());
        }

        return $this->b2bBudgetUsageTrackerInstance;
    }

    protected function getB2bBudgetRenewHelper(): BudgetRenewHelper
    {
        if (! $this->b2bBudgetRenewHelperInstance instanceof BudgetRenewHelper) {
            $this->b2bBudgetRenewHelperInstance = new BudgetRenewHelper(static::getContainer());
        }

        return $this->b2bBudgetRenewHelperInstance;
    }

    protected function getB2bBudgetNotificationHelper(): BudgetNotificationHelper
    {
        if (! $this->b2bBudgetNotificationHelperInstance instanceof BudgetNotificationHelper) {
            $this->b2bBudgetNotificationHelperInstance = new BudgetNotificationHelper(static::getContainer());
        }

        return $this->b2bBudgetNotificationHelperInstance;
    }

    protected function getB2bBudgetValidationHelper(): BudgetValidationHelper
    {
        if (! $this->b2bBudgetValidationHelperInstance instanceof BudgetValidationHelper) {
            $this->b2bBudgetValidationHelperInstance = new BudgetValidationHelper(static::getContainer());
        }

        return $this->b2bBudgetValidationHelperInstance;
    }

    protected function b2bBudgetTrackUsage(
        string $budgetId,
        float $amount,
        string $description = '',
        ?Context $context = null
    ): BudgetEntity {
        return $this->getB2bBudgetUsageTracker()->trackUsage($budgetId, $amount, $description, $context);
    }

    /**
     * @param array<int, array<string, mixed>> $transactions
     */
    protected function b2bBudgetSimulateUsage(
        string $budgetId,
        array $transactions,
        ?Context $context = null
    ): BudgetEntity {
        return $this->getB2bBudgetUsageTracker()->simulateUsage($budgetId, $transactions, $context);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function b2bBudgetGetUsageHistory(string $budgetId): array
    {
        return $this->getB2bBudgetUsageTracker()->getUsageHistory($budgetId);
    }

    protected function b2bBudgetClearHistory(?string $budgetId = null): void
    {
        $this->getB2bBudgetUsageTracker()->clearHistory($budgetId);
    }

    protected function b2bBudgetGetTotalTrackedUsage(string $budgetId): float
    {
        return $this->getB2bBudgetUsageTracker()->getTotalTrackedUsage($budgetId);
    }

    protected function b2bBudgetFillToPercentage(string $budgetId, float $percentage, ?Context $context = null): BudgetEntity
    {
        return $this->getB2bBudgetUsageTracker()->fillToPercentage($budgetId, $percentage, $context);
    }

    protected function b2bBudgetExceed(string $budgetId, float $excessAmount = 100.0, ?Context $context = null): BudgetEntity
    {
        return $this->getB2bBudgetUsageTracker()->exceedBudget($budgetId, $excessAmount, $context);
    }

    protected function b2bBudgetRenew(string $budgetId, ?Context $context = null): BudgetEntity
    {
        return $this->getB2bBudgetRenewHelper()->renewBudget($budgetId, $context);
    }

    protected function b2bBudgetShouldRenew(string $budgetId, ?Context $context = null): bool
    {
        return $this->getB2bBudgetRenewHelper()->shouldRenew($budgetId, $context);
    }

    protected function b2bBudgetGetNextRenewalDate(string $budgetId, ?Context $context = null): ?\DateTimeInterface
    {
        return $this->getB2bBudgetRenewHelper()->getNextRenewalDate($budgetId, $context);
    }

    protected function b2bBudgetSimulateTimePassage(
        string $budgetId,
        \DateTimeInterface $targetDate,
        ?Context $context = null
    ): BudgetEntity {
        return $this->getB2bBudgetRenewHelper()->simulateTimePassage($budgetId, $targetDate, $context);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function b2bBudgetTestRenewalCycle(
        string $budgetId,
        int $cycles,
        ?Context $context = null
    ): array {
        return $this->getB2bBudgetRenewHelper()->testRenewalCycle($budgetId, $cycles, $context);
    }

    protected function b2bBudgetNotificationShouldNotify(string $budgetId, ?Context $context = null): bool
    {
        return $this->getB2bBudgetNotificationHelper()->shouldNotify($budgetId, $context);
    }

    protected function b2bBudgetNotificationHasReachedThreshold(BudgetEntity $budget): bool
    {
        return $this->getB2bBudgetNotificationHelper()->hasReachedThreshold($budget);
    }

    protected function b2bBudgetMarkNotificationSent(string $budgetId, ?Context $context = null): BudgetEntity
    {
        return $this->getB2bBudgetNotificationHelper()->markAsSent($budgetId, $context);
    }

    protected function b2bBudgetResetNotificationStatus(string $budgetId, ?Context $context = null): BudgetEntity
    {
        return $this->getB2bBudgetNotificationHelper()->resetNotificationStatus($budgetId, $context);
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function b2bBudgetGetNotificationRecipients(string $budgetId, ?Context $context = null): array
    {
        return $this->getB2bBudgetNotificationHelper()->getRecipients($budgetId, $context);
    }

    /**
     * @return array<string, mixed>
     */
    protected function b2bBudgetSimulateNotificationTrigger(string $budgetId, ?Context $context = null): array
    {
        return $this->getB2bBudgetNotificationHelper()->simulateNotificationTrigger($budgetId, $context);
    }

    /**
     * @param array<int, float> $usageAmounts
     *
     * @return array<int, array<string, mixed>>
     */
    protected function b2bBudgetTestNotificationScenarios(
        string $budgetId,
        array $usageAmounts,
        ?Context $context = null
    ): array {
        return $this->getB2bBudgetNotificationHelper()->testNotificationScenarios(
            $budgetId,
            $usageAmounts,
            $context
        );
    }

    protected function b2bBudgetExceedsCart(Cart $cart, string $budgetId, ?Context $context = null): bool
    {
        return $this->getB2bBudgetValidationHelper()->exceedsBudget($cart, $budgetId, $context);
    }

    protected function b2bBudgetExceedsAmount(float $amount, BudgetEntity $budget): bool
    {
        return $this->getB2bBudgetValidationHelper()->exceedsBudgetAmount($amount, $budget);
    }

    protected function b2bBudgetGetRemaining(BudgetEntity $budget): float
    {
        return $this->getB2bBudgetValidationHelper()->getRemainingBudget($budget);
    }

    protected function b2bBudgetIsActive(string $budgetId, ?Context $context = null): bool
    {
        return $this->getB2bBudgetValidationHelper()->isBudgetActive($budgetId, $context);
    }

    protected function b2bBudgetRequiresApproval(float $amount, string $budgetId, ?Context $context = null): bool
    {
        return $this->getB2bBudgetValidationHelper()->requiresApproval($amount, $budgetId, $context);
    }

    protected function b2bBudgetValidationSimulateUsage(
        string $budgetId,
        float $amount,
        ?Context $context = null
    ): BudgetEntity {
        return $this->getB2bBudgetValidationHelper()->simulateBudgetUsage($budgetId, $amount, $context);
    }

    protected function b2bBudgetResetUsage(string $budgetId, ?Context $context = null): BudgetEntity
    {
        return $this->getB2bBudgetValidationHelper()->resetBudgetUsage($budgetId, $context);
    }

    /**
     * @return array<BudgetEntity>
     */
    protected function b2bBudgetGetActiveBudgetsForOrganization(string $organizationId, ?Context $context = null): array
    {
        return $this->getB2bBudgetValidationHelper()->getActiveBudgetsForOrganization($organizationId, $context);
    }

    protected function b2bBudgetValidationShouldNotify(string $budgetId, ?Context $context = null): bool
    {
        return $this->getB2bBudgetValidationHelper()->shouldNotify($budgetId, $context);
    }

    protected function b2bBudgetGetUsagePercentage(string $budgetId, ?Context $context = null): float
    {
        return $this->getB2bBudgetValidationHelper()->getUsagePercentage($budgetId, $context);
    }

    protected function b2bBudgetAssertExceeded(string $budgetId, ?Context $context = null): void
    {
        $this->getB2bBudgetValidationHelper()->assertBudgetExceeded($budgetId, $context);
    }

    protected function b2bBudgetAssertNotExceeded(string $budgetId, ?Context $context = null): void
    {
        $this->getB2bBudgetValidationHelper()->assertBudgetNotExceeded($budgetId, $context);
    }

    protected function b2bBudgetAssertNotificationTriggered(string $budgetId, ?Context $context = null): void
    {
        $this->getB2bBudgetValidationHelper()->assertBudgetNotificationTriggered($budgetId, $context);
    }
}
