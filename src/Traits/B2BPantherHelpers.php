<?php

namespace Algoritma\ShopwareTestUtils\Traits;

/**
 * B2B-specific browser interactions for Panther acceptance tests.
 *
 * This trait provides B2B storefront actions for employee login, quote management,
 * order approvals, shopping lists, budgets, and organization switching.
 *
 * Usage:
 * ```php
 * class MyB2BTest extends AbstractAcceptanceTestCase
 * {
 *     use B2BPantherHelpers;
 *
 *     public function testEmployeeQuote(): void
 *     {
 *         $this->loginAsEmployee('employee@example.com', 'password');
 *         $this->requestQuoteFromCart();
 *         $this->assertQuoteRequestSuccess();
 *     }
 * }
 * ```
 *
 * Requirements:
 * - Test class must have `$client` property (Panther Client)
 * - Test class must have `getContainer()` method
 * - Shopware Commercial B2B plugin must be installed
 */
trait B2BPantherHelpers
{
    // --- Employee Authentication ---

    /**
     * Login to storefront as a B2B employee.
     */
    protected function loginAsEmployee(string $email, string $password): void
    {
        $this->client->request('GET', '/b2b/employee/login');
        $this->waitForB2BPageLoad();

        $crawler = $this->client->getCrawler();
        $form = $crawler->filter('form[action*="employee"][action*="login"]')->form();

        $form['username'] = $email;
        $form['password'] = $password;

        $this->client->submit($form);
        $this->waitForB2BPageLoad();
    }

    /**
     * Logout from employee session.
     */
    protected function logoutEmployee(): void
    {
        $this->client->request('GET', '/b2b/employee/logout');
        $this->waitForB2BPageLoad();
    }

    // --- Organization Management ---

    /**
     * Switch to a different organization unit.
     */
    protected function switchOrganizationUnit(string $organizationName): void
    {
        $this->navigateToEmployeeDashboard();

        $crawler = $this->client->getCrawler();
        $organizationSelector = $crawler->filter('select[name="organizationId"], .organization-switcher');

        if ($organizationSelector->count() === 0) {
            throw new \RuntimeException('Organization switcher not found');
        }

        // Find the organization by name and select it
        $this->client->executeScript(
            sprintf(
                'Array.from(document.querySelectorAll("select[name=\'organizationId\'] option")).find(opt => opt.textContent.includes("%s")).selected = true;',
                addslashes($organizationName)
            )
        );

        $this->clickB2BElement('button[type="submit"][form*="organization"]');
        $this->waitForB2BAjaxComplete();
    }

    // --- Quote Management ---

    /**
     * Request a quote from the current cart.
     */
    protected function requestQuoteFromCart(): void
    {
        $this->client->request('GET', '/checkout/cart');
        $this->waitForB2BPageLoad();

        $this->clickB2BElement('.btn-request-quote, button[data-action="request-quote"]');
        $this->waitForB2BAjaxComplete();
    }

    /**
     * Navigate to quote management page.
     */
    protected function accessQuoteManagement(): void
    {
        $this->client->request('GET', '/b2b/quote/list');
        $this->waitForB2BPageLoad();
    }

    /**
     * Add a comment to a quote.
     */
    protected function addCommentToQuote(string $quoteId, string $comment): void
    {
        $this->client->request('GET', sprintf('/b2b/quote/%s', $quoteId));
        $this->waitForB2BPageLoad();

        $this->fillB2BField('textarea[name="comment"]', $comment);
        $this->clickB2BElement('button[type="submit"][form*="comment"]');
        $this->waitForB2BAjaxComplete();
    }

    /**
     * Accept a quote in the storefront.
     */
    protected function acceptQuoteInStorefront(string $quoteId): void
    {
        $this->client->request('GET', sprintf('/b2b/quote/%s', $quoteId));
        $this->waitForB2BPageLoad();

        $this->clickB2BElement('.btn-accept-quote, button[data-action="accept-quote"]');
        $this->waitForB2BAjaxComplete();
    }

    /**
     * Decline a quote in the storefront.
     */
    protected function declineQuoteInStorefront(string $quoteId): void
    {
        $this->client->request('GET', sprintf('/b2b/quote/%s', $quoteId));
        $this->waitForB2BPageLoad();

        $this->clickB2BElement('.btn-decline-quote, button[data-action="decline-quote"]');
        $this->waitForB2BAjaxComplete();
    }

    // --- Order Approval ---

    /**
     * Submit an order for approval (when budget/rules require it).
     */
    protected function submitOrderForApproval(): void
    {
        $this->client->request('GET', '/checkout/confirm');
        $this->waitForB2BPageLoad();

        $this->clickB2BElement('.btn-submit-approval, button[data-action="request-approval"]');
        $this->waitForB2BPageLoad();
    }

    /**
     * Navigate to approval queue.
     */
    protected function accessApprovalQueue(): void
    {
        $this->client->request('GET', '/b2b/order/approval');
        $this->waitForB2BPageLoad();
    }

    /**
     * Approve a pending order as a manager.
     */
    protected function approveOrderAsManager(string $orderId): void
    {
        $this->accessApprovalQueue();

        $this->clickB2BElement(sprintf('.btn-approve-order[data-order-id="%s"]', $orderId));
        $this->waitForB2BAjaxComplete();
    }

    /**
     * Decline a pending order as a manager.
     */
    protected function declineOrderAsManager(string $orderId, string $reason = ''): void
    {
        $this->accessApprovalQueue();

        if ($reason !== '' && $reason !== '0') {
            $this->fillB2BField(sprintf('textarea[name="declineReason"][data-order-id="%s"]', $orderId), $reason);
        }

        $this->clickB2BElement(sprintf('.btn-decline-order[data-order-id="%s"]', $orderId));
        $this->waitForB2BAjaxComplete();
    }

    // --- Shopping Lists ---

    /**
     * Add a product to a shopping list.
     */
    protected function addProductToShoppingList(string $productId, string $listName): void
    {
        $this->client->request('GET', sprintf('/detail/%s', $productId));
        $this->waitForB2BPageLoad();

        // Open shopping list dropdown/modal
        $this->clickB2BElement('.btn-add-to-list, button[data-action="add-to-shopping-list"]');
        $this->waitForB2BAjaxComplete();

        // Select the shopping list
        $this->client->executeScript(
            sprintf(
                'Array.from(document.querySelectorAll(".shopping-list-option")).find(opt => opt.textContent.includes("%s")).click();',
                addslashes($listName)
            )
        );

        $this->waitForB2BAjaxComplete();
    }

    /**
     * Navigate to shopping lists page.
     */
    protected function accessShoppingLists(): void
    {
        $this->client->request('GET', '/b2b/shopping-list');
        $this->waitForB2BPageLoad();
    }

    /**
     * Add shopping list to cart.
     */
    protected function addShoppingListToCart(string $listName): void
    {
        $this->accessShoppingLists();

        $this->clickB2BElement(sprintf('.btn-list-to-cart[data-list-name="%s"]', $listName));
        $this->waitForB2BAjaxComplete();
    }

    // --- Budget Management ---

    /**
     * Check current budget status.
     */
    protected function checkBudgetStatus(): void
    {
        $this->client->request('GET', '/b2b/budget/status');
        $this->waitForB2BPageLoad();
    }

    /**
     * Navigate to budget overview page.
     */
    protected function accessBudgetOverview(): void
    {
        $this->client->request('GET', '/b2b/budget');
        $this->waitForB2BPageLoad();
    }

    // --- Navigation ---

    /**
     * Navigate to employee dashboard.
     */
    protected function navigateToEmployeeDashboard(): void
    {
        $this->client->request('GET', '/b2b/employee/dashboard');
        $this->waitForB2BPageLoad();
    }

    /**
     * Navigate to employee account page.
     */
    protected function navigateToEmployeeAccount(): void
    {
        $this->client->request('GET', '/b2b/employee/account');
        $this->waitForB2BPageLoad();
    }

    // --- B2B Assertions ---

    /**
     * Assert that employee is logged in and name is visible.
     */
    protected function assertEmployeeLoggedIn(string $employeeName): void
    {
        $crawler = $this->client->getCrawler();
        $userInfo = $crawler->filter('.employee-name, .user-info, [data-employee-name]');

        $found = false;
        foreach ($userInfo as $element) {
            if (str_contains((string) $element->textContent, $employeeName)) {
                $found = true;

                break;
            }
        }

        assert($found, sprintf('Employee "%s" not found in user info', $employeeName));
    }

    /**
     * Assert that organization has been switched successfully.
     */
    protected function assertOrganizationSwitched(string $organizationName): void
    {
        $crawler = $this->client->getCrawler();
        $orgInfo = $crawler->filter('.current-organization, [data-current-organization]');

        assert($orgInfo->count() > 0, 'Organization info not found');
        assert(
            str_contains($orgInfo->text(), $organizationName),
            sprintf('Expected organization "%s" but got "%s"', $organizationName, $orgInfo->text())
        );
    }

    /**
     * Assert that quote request was successful.
     */
    protected function assertQuoteRequestSuccess(): void
    {
        $crawler = $this->client->getCrawler();
        $successMessage = $crawler->filter('.alert-success, .flash-success');

        assert($successMessage->count() > 0, 'Quote request success message not found');
    }

    /**
     * Assert that an order is visible in the approval queue.
     */
    protected function assertOrderInApprovalQueue(string $orderNumber): void
    {
        $this->accessApprovalQueue();

        $crawler = $this->client->getCrawler();
        $orders = $crawler->filter('.pending-order, [data-order-number]');

        $found = false;
        foreach ($orders as $order) {
            if (str_contains((string) $order->textContent, $orderNumber)) {
                $found = true;

                break;
            }
        }

        assert($found, sprintf('Order "%s" not found in approval queue', $orderNumber));
    }

    /**
     * Assert that budget limit warning is visible.
     */
    protected function assertBudgetLimitWarningVisible(): void
    {
        $crawler = $this->client->getCrawler();
        $warning = $crawler->filter('.budget-warning, .alert-warning[data-budget-warning]');

        assert($warning->count() > 0, 'Budget limit warning not visible');
    }

    /**
     * Assert that quote has a specific status.
     */
    protected function assertQuoteStatusVisible(string $status): void
    {
        $crawler = $this->client->getCrawler();
        $statusBadge = $crawler->filter('.quote-status, [data-quote-status]');

        assert($statusBadge->count() > 0, 'Quote status not found');
        assert(
            str_contains(strtolower($statusBadge->text()), strtolower($status)),
            sprintf('Expected quote status "%s" but got "%s"', $status, $statusBadge->text())
        );
    }

    /**
     * Assert that employee has access to a specific feature.
     */
    protected function assertEmployeeHasAccessToFeature(string $featureName): void
    {
        $crawler = $this->client->getCrawler();
        $navigation = $crawler->filter('.b2b-navigation, .employee-menu');

        $found = false;
        foreach ($navigation as $nav) {
            if (str_contains((string) $nav->textContent, $featureName)) {
                $found = true;

                break;
            }
        }

        assert($found, sprintf('Employee does not have access to feature "%s"', $featureName));
    }

    /**
     * Assert that shopping list contains a product.
     */
    protected function assertShoppingListContainsProduct(string $listName, string $productName): void
    {
        $this->accessShoppingLists();

        $this->clickB2BElement(sprintf('.shopping-list-item[data-list-name="%s"]', $listName));
        $this->waitForB2BAjaxComplete();

        $crawler = $this->client->getCrawler();
        $products = $crawler->filter('.list-product, .shopping-list-product');

        $found = false;
        foreach ($products as $product) {
            if (str_contains((string) $product->textContent, $productName)) {
                $found = true;

                break;
            }
        }

        assert($found, sprintf('Product "%s" not found in shopping list "%s"', $productName, $listName));
    }

    // --- Low-Level B2B Helpers ---

    /**
     * Click a B2B-specific element.
     */
    private function clickB2BElement(string $selector): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        if ($element->count() === 0) {
            throw new \RuntimeException(sprintf('B2B element "%s" not found', $selector));
        }

        $element->first()->click();
    }

    /**
     * Fill a B2B form field.
     */
    private function fillB2BField(string $selector, string $value): void
    {
        $this->client->executeScript(
            sprintf('document.querySelector("%s").value = "%s";', addslashes($selector), addslashes($value))
        );
    }

    /**
     * Wait for B2B page to fully load.
     */
    private function waitForB2BPageLoad(int $timeout = 10): void
    {
        $this->client->waitFor('body', $timeout);
        $this->client->waitForVisibility('body');

        // Wait for B2B-specific loading indicators
        $script = 'return document.querySelectorAll(".b2b-loading, [data-b2b-loading]").length === 0;';
        $this->client->waitFor(fn () => $this->client->executeScript($script) === true, $timeout);
    }

    /**
     * Wait for B2B AJAX requests to complete.
     */
    private function waitForB2BAjaxComplete(int $timeout = 10): void
    {
        // Wait for B2B loading indicators
        $script = 'return document.querySelectorAll(".b2b-loading, .ajax-loading, [data-loading]").length === 0;';

        $this->client->waitFor(fn () => $this->client->executeScript($script) === true, $timeout);

        // Wait for jQuery if present
        $jQueryScript = 'return typeof jQuery !== "undefined" ? jQuery.active === 0 : true;';
        $this->client->waitFor(fn () => $this->client->executeScript($jQueryScript) === true, $timeout);
    }
}
