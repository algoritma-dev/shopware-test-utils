<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Symfony\Component\Panther\Client;

/**
 * Helper for Shopware-specific browser interactions using Panther.
 *
 * Provides high-level methods for common Shopware storefront actions:
 * - Login/authentication
 * - Product navigation
 * - Cart operations
 * - Checkout flow
 * - Search and filtering
 *
 * Usage:
 * ```php
 * $helper = new ShopwarePantherHelper($this->client, $this->getContainer());
 * $helper->loginToStorefront('customer@example.com', 'password');
 * $helper->navigateToProduct($productId);
 * $helper->addProductToCartViaButton($productId);
 * ```
 */
class ShopwarePantherHelper
{
    public function __construct(
        private readonly Client $client
    ) {}

    // --- Authentication ---

    /**
     * Login to storefront as a customer.
     */
    public function loginToStorefront(string $email, string $password): void
    {
        $this->client->request('GET', '/account/login');
        $this->waitForPageLoad();

        $crawler = $this->client->getCrawler();
        $form = $crawler->filter('form[action*="login"]')->form();

        $form['username'] = $email;
        $form['password'] = $password;

        $this->client->submit($form);
        $this->waitForPageLoad();
    }

    /**
     * Logout from storefront.
     */
    public function logout(): void
    {
        $this->client->request('GET', '/account/logout');
        $this->waitForPageLoad();
    }

    // --- Product Navigation ---

    /**
     * Navigate to product detail page.
     */
    public function navigateToProduct(string $productId): void
    {
        // Use the technical product detail route
        $this->client->request('GET', sprintf('/detail/%s', $productId));
        $this->waitForPageLoad();
    }

    /**
     * Navigate to a category listing page.
     */
    public function navigateToCategory(string $categoryId): void
    {
        $this->client->request('GET', sprintf('/navigation/%s', $categoryId));
        $this->waitForPageLoad();
    }

    // --- Cart Operations ---

    /**
     * Add product to cart by clicking the "Add to Cart" button.
     */
    public function addProductToCartViaButton(string $productId, int $quantity = 1): void
    {
        // First navigate to product if not already there
        if (! str_contains($this->client->getCurrentURL(), '/detail/')) {
            $this->navigateToProduct($productId);
        }

        // Set quantity if needed
        if ($quantity > 1) {
            $this->fillField('input[name="lineItems[' . $productId . '][quantity]"]', (string) $quantity);
        }

        // Click add to cart button
        $this->clickElement('.btn-buy, button[type="submit"][form*="addToCart"]');
        $this->waitForAjaxComplete();
    }

    /**
     * Navigate to cart page.
     */
    public function navigateToCart(): void
    {
        $this->client->request('GET', '/checkout/cart');
        $this->waitForPageLoad();
    }

    /**
     * Remove a line item from cart.
     */
    public function removeLineItemFromCart(string $lineItemId): void
    {
        $this->clickElement(sprintf('form[action*="line-item/delete"] input[value="%s"] ~ button', $lineItemId));
        $this->waitForAjaxComplete();
    }

    /**
     * Update line item quantity in cart.
     */
    public function updateLineItemQuantity(string $lineItemId, int $quantity): void
    {
        $this->fillField(sprintf('input[name="lineItems[%s][quantity]"]', $lineItemId), (string) $quantity);
        $this->clickElement('.cart-item-quantity-update, button[type="submit"][form*="quantity"]');
        $this->waitForAjaxComplete();
    }

    // --- Checkout Flow ---

    /**
     * Proceed to checkout page from cart.
     */
    public function proceedToCheckoutPage(): void
    {
        $this->client->request('GET', '/checkout/confirm');
        $this->waitForPageLoad();
    }

    /**
     * Fill checkout form with customer data.
     *
     * @param array<string, mixed> $data
     */
    public function fillCheckoutForm(array $data): void
    {
        $this->client->getCrawler();

        // Fill billing address fields
        if (isset($data['billingAddress'])) {
            foreach ($data['billingAddress'] as $field => $value) {
                $selector = sprintf('input[name*="billingAddress[%s]"]', $field);
                $this->fillField($selector, (string) $value);
            }
        }

        // Fill shipping address fields
        if (isset($data['shippingAddress'])) {
            foreach ($data['shippingAddress'] as $field => $value) {
                $selector = sprintf('input[name*="shippingAddress[%s]"]', $field);
                $this->fillField($selector, (string) $value);
            }
        }

        // Select payment method
        if (isset($data['paymentMethodId'])) {
            $this->selectRadio(sprintf('input[name="paymentMethodId"][value="%s"]', $data['paymentMethodId']));
        }

        // Select shipping method
        if (isset($data['shippingMethodId'])) {
            $this->selectRadio(sprintf('input[name="shippingMethodId"][value="%s"]', $data['shippingMethodId']));
        }
    }

    /**
     * Complete the order (submit final checkout form).
     */
    public function completeOrder(): void
    {
        $this->clickElement('#confirmFormSubmit, button[type="submit"][form*="confirmOrder"]');
        $this->waitForPageLoad();
    }

    // --- Search & Filtering ---

    /**
     * Search for a product using the storefront search.
     */
    public function searchProduct(string $term): void
    {
        $this->fillField('input[name="search"]', $term);
        $this->client->submitForm('Search', ['search' => $term]);
        $this->waitForPageLoad();
    }

    /**
     * Apply filters on product listing page.
     *
     * @param array<string, string> $filters
     */
    public function filterProducts(array $filters): void
    {
        foreach ($filters as $filterName => $filterValue) {
            $this->clickElement(sprintf('input[name="%s"][value="%s"]', $filterName, $filterValue));
            $this->waitForAjaxComplete();
        }
    }

    // --- Assertions ---

    /**
     * Assert that a product is visible in the listing.
     */
    public function assertProductVisibleInListing(string $productName): void
    {
        $crawler = $this->client->getCrawler();
        $productElements = $crawler->filter('.product-name, .product-title');

        $found = false;
        foreach ($productElements as $element) {
            if (str_contains((string) $element->textContent, $productName)) {
                $found = true;

                break;
            }
        }

        assert($found, sprintf('Product "%s" not found in listing', $productName));
    }

    /**
     * Assert that the cart total matches expected value.
     */
    public function assertCartTotal(float $expectedTotal): void
    {
        $crawler = $this->client->getCrawler();
        $totalElement = $crawler->filter('.cart-total-price, [data-cart-total-price]');

        assert($totalElement->count() > 0, 'Cart total element not found');

        $totalText = $totalElement->text();
        $totalText = preg_replace('/[^0-9.,]/', '', $totalText);
        $totalText = str_replace(',', '.', $totalText);
        $actualTotal = (float) $totalText;

        assert(
            abs($actualTotal - $expectedTotal) < 0.01,
            sprintf('Expected cart total %.2f, got %.2f', $expectedTotal, $actualTotal)
        );
    }

    /**
     * Assert that cart contains a specific product.
     */
    public function assertCartContainsProduct(string $productName): void
    {
        $this->navigateToCart();

        $crawler = $this->client->getCrawler();
        $lineItems = $crawler->filter('.line-item-label, .cart-item-label');

        $found = false;
        foreach ($lineItems as $item) {
            if (str_contains((string) $item->textContent, $productName)) {
                $found = true;

                break;
            }
        }

        assert($found, sprintf('Product "%s" not found in cart', $productName));
    }

    /**
     * Assert that a specific checkout step is visible.
     */
    public function assertCheckoutStepVisible(string $step): void
    {
        $crawler = $this->client->getCrawler();
        $stepElement = $crawler->filter(sprintf('.checkout-step[data-step="%s"], .checkout-%s-step', $step, $step));

        assert($stepElement->count() > 0, sprintf('Checkout step "%s" not visible', $step));
    }

    /**
     * Verify that a success message is displayed.
     */
    public function verifySuccessMessage(string $expectedText): void
    {
        $crawler = $this->client->getCrawler();
        $messages = $crawler->filter('.alert-success, .flash-success, [role="alert"].success');

        $found = false;
        foreach ($messages as $message) {
            if (str_contains((string) $message->textContent, $expectedText)) {
                $found = true;

                break;
            }
        }

        assert($found, sprintf('Success message "%s" not found', $expectedText));
    }

    // --- Low-Level Helpers ---

    /**
     * Click an element by selector.
     */
    public function clickElement(string $selector): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        if ($element->count() === 0) {
            throw new \RuntimeException(sprintf('Element "%s" not found', $selector));
        }

        $element->first()->click();
    }

    /**
     * Fill a form field.
     */
    public function fillField(string $selector, string $value): void
    {
        $crawler = $this->client->getCrawler();
        $field = $crawler->filter($selector);

        if ($field->count() === 0) {
            throw new \RuntimeException(sprintf('Field "%s" not found', $selector));
        }

        // Use JavaScript to set value for better compatibility
        $this->client->executeScript(
            sprintf('document.querySelector("%s").value = "%s";', addslashes($selector), addslashes($value))
        );
    }

    /**
     * Select a radio button.
     */
    public function selectRadio(string $selector): void
    {
        $this->client->executeScript(
            sprintf('document.querySelector("%s").checked = true;', addslashes($selector))
        );
    }

    /**
     * Wait for the page to fully load.
     */
    public function waitForPageLoad(int $timeout = 10): void
    {
        $this->client->waitFor('body', $timeout);

        // Wait for document ready state
        $this->client->waitForVisibility('body');
    }

    /**
     * Wait for all AJAX requests to complete.
     */
    public function waitForAjaxComplete(int $timeout = 10): void
    {
        // Wait for loading indicators to disappear
        $script = 'return document.querySelectorAll(".ajax-loading, .loading-indicator, [data-loading]").length === 0;';

        $this->client->waitFor(fn (): bool => $this->client->executeScript($script) === true, $timeout);

        // Also wait for jQuery if present
        $jQueryScript = 'return typeof jQuery !== "undefined" ? jQuery.active === 0 : true;';
        $this->client->waitFor(fn (): bool => $this->client->executeScript($jQueryScript) === true, $timeout);
    }
}
