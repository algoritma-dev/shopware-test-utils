<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Symfony\Component\Panther\Client;

/**
 * Browser-level assertions for acceptance tests.
 *
 * This trait provides high-level assertions for Panther-based acceptance tests.
 * It expects the test class to have a `$client` property of type `Client`.
 */
trait AcceptanceAssertionsTrait
{
    /**
     * Assert that the page contains specific text.
     */
    protected function assertPageContainsText(string $text, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $content = $crawler->text();

        $this->assertStringContainsString(
            $text,
            $content,
            $message ?: sprintf('Failed asserting that page contains text "%s"', $text)
        );
    }

    /**
     * Assert that the page does not contain specific text.
     */
    protected function assertPageNotContainsText(string $text, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $content = $crawler->text();

        $this->assertStringNotContainsString(
            $text,
            $content,
            $message ?: sprintf('Failed asserting that page does not contain text "%s"', $text)
        );
    }

    /**
     * Assert that an element is visible on the page.
     */
    protected function assertElementVisible(string $selector, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        $this->assertGreaterThan(
            0,
            $element->count(),
            $message ?: sprintf('Failed asserting that element "%s" is visible', $selector)
        );

        // Check if element is actually visible (not hidden by CSS)
        $isVisible = $this->client->executeScript(
            sprintf('return document.querySelector("%s") !== null && window.getComputedStyle(document.querySelector("%s")).display !== "none";', addslashes($selector), addslashes($selector))
        );

        $this->assertTrue(
            $isVisible,
            $message ?: sprintf('Element "%s" exists but is not visible', $selector)
        );
    }

    /**
     * Assert that an element is not visible on the page.
     */
    protected function assertElementNotVisible(string $selector, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        if ($element->count() === 0) {
            // Element doesn't exist at all, that's fine
            $this->assertTrue(true);

            return;
        }

        // Element exists, check if it's hidden
        $isVisible = $this->client->executeScript(
            sprintf('return document.querySelector("%s") !== null && window.getComputedStyle(document.querySelector("%s")).display !== "none";', addslashes($selector), addslashes($selector))
        );

        $this->assertFalse(
            $isVisible,
            $message ?: sprintf('Failed asserting that element "%s" is not visible', $selector)
        );
    }

    /**
     * Assert that an element exists on the page (regardless of visibility).
     */
    protected function assertElementExists(string $selector, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        $this->assertGreaterThan(
            0,
            $element->count(),
            $message ?: sprintf('Failed asserting that element "%s" exists', $selector)
        );
    }

    /**
     * Assert that an element does not exist on the page.
     */
    protected function assertElementNotExists(string $selector, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        $this->assertEquals(
            0,
            $element->count(),
            $message ?: sprintf('Failed asserting that element "%s" does not exist', $selector)
        );
    }

    /**
     * Assert that an element has a specific CSS class.
     */
    protected function assertElementHasClass(string $selector, string $class, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        $this->assertGreaterThan(
            0,
            $element->count(),
            sprintf('Element "%s" not found', $selector)
        );

        $classes = $element->attr('class');
        $classList = $classes ? explode(' ', $classes) : [];

        $this->assertContains(
            $class,
            $classList,
            $message ?: sprintf('Failed asserting that element "%s" has class "%s"', $selector, $class)
        );
    }

    /**
     * Assert that an element does not have a specific CSS class.
     */
    protected function assertElementNotHasClass(string $selector, string $class, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        $this->assertGreaterThan(
            0,
            $element->count(),
            sprintf('Element "%s" not found', $selector)
        );

        $classes = $element->attr('class');
        $classList = $classes ? explode(' ', $classes) : [];

        $this->assertNotContains(
            $class,
            $classList,
            $message ?: sprintf('Failed asserting that element "%s" does not have class "%s"', $selector, $class)
        );
    }

    /**
     * Assert that an element has a specific attribute value.
     */
    protected function assertElementAttributeEquals(string $selector, string $attribute, string $value, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        $this->assertGreaterThan(
            0,
            $element->count(),
            sprintf('Element "%s" not found', $selector)
        );

        $actualValue = $element->attr($attribute);

        $this->assertEquals(
            $value,
            $actualValue,
            $message ?: sprintf('Failed asserting that element "%s" has attribute "%s" with value "%s"', $selector, $attribute, $value)
        );
    }

    /**
     * Assert that an element contains specific text.
     */
    protected function assertElementContainsText(string $selector, string $text, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        $this->assertGreaterThan(
            0,
            $element->count(),
            sprintf('Element "%s" not found', $selector)
        );

        $elementText = $element->text();

        $this->assertStringContainsString(
            $text,
            $elementText,
            $message ?: sprintf('Failed asserting that element "%s" contains text "%s"', $selector, $text)
        );
    }

    /**
     * Assert that the current URL matches a pattern.
     */
    protected function assertCurrentUrlMatches(string $pattern, string $message = ''): void
    {
        $currentUrl = $this->client->getCurrentURL();

        $this->assertMatchesRegularExpression(
            $pattern,
            $currentUrl,
            $message ?: sprintf('Failed asserting that URL "%s" matches pattern "%s"', $currentUrl, $pattern)
        );
    }

    /**
     * Assert that the current URL contains a string.
     */
    protected function assertCurrentUrlContains(string $needle, string $message = ''): void
    {
        $currentUrl = $this->client->getCurrentURL();

        $this->assertStringContainsString(
            $needle,
            $currentUrl,
            $message ?: sprintf('Failed asserting that URL "%s" contains "%s"', $currentUrl, $needle)
        );
    }

    /**
     * Assert that the page title contains specific text.
     */
    protected function assertPageTitleContains(string $title, string $message = ''): void
    {
        $pageTitle = $this->client->getTitle();

        $this->assertStringContainsString(
            $title,
            $pageTitle,
            $message ?: sprintf('Failed asserting that page title "%s" contains "%s"', $pageTitle, $title)
        );
    }

    /**
     * Assert that a JavaScript alert is present.
     */
    protected function assertAlertPresent(string $message = ''): void
    {
        $hasAlert = $this->client->executeScript('return typeof window.alert !== "undefined";');

        $this->assertTrue(
            $hasAlert,
            $message ?: 'Failed asserting that a JavaScript alert is present'
        );
    }

    /**
     * Assert that no JavaScript errors are present in the console.
     */
    protected function assertNoJavaScriptErrors(string $message = ''): void
    {
        $logs = $this->client->executeScript('return window.console.errors || [];');

        $this->assertEmpty(
            $logs,
            $message ?: 'Failed asserting that no JavaScript errors are present'
        );
    }

    /**
     * Assert that a specific number of elements match the selector.
     */
    protected function assertElementCount(string $selector, int $count, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $elements = $crawler->filter($selector);

        $this->assertEquals(
            $count,
            $elements->count(),
            $message ?: sprintf('Failed asserting that "%s" elements match selector "%s"', $count, $selector)
        );
    }

    /**
     * Assert that an element is enabled (not disabled).
     */
    protected function assertElementEnabled(string $selector, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        $this->assertGreaterThan(
            0,
            $element->count(),
            sprintf('Element "%s" not found', $selector)
        );

        $isDisabled = $element->attr('disabled') !== null;

        $this->assertFalse(
            $isDisabled,
            $message ?: sprintf('Failed asserting that element "%s" is enabled', $selector)
        );
    }

    /**
     * Assert that an element is disabled.
     */
    protected function assertElementDisabled(string $selector, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $element = $crawler->filter($selector);

        $this->assertGreaterThan(
            0,
            $element->count(),
            sprintf('Element "%s" not found', $selector)
        );

        $isDisabled = $element->attr('disabled') !== null;

        $this->assertTrue(
            $isDisabled,
            $message ?: sprintf('Failed asserting that element "%s" is disabled', $selector)
        );
    }

    /**
     * Assert that a form field has a specific value.
     */
    protected function assertFieldValue(string $fieldSelector, string $expectedValue, string $message = ''): void
    {
        $crawler = $this->client->getCrawler();
        $field = $crawler->filter($fieldSelector);

        $this->assertGreaterThan(
            0,
            $field->count(),
            sprintf('Field "%s" not found', $fieldSelector)
        );

        $actualValue = $field->attr('value');

        $this->assertEquals(
            $expectedValue,
            $actualValue,
            $message ?: sprintf('Failed asserting that field "%s" has value "%s"', $fieldSelector, $expectedValue)
        );
    }
}
