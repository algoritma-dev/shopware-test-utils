<?php

namespace Algoritma\ShopwareTestUtils\Core;

use Algoritma\ShopwareTestUtils\Traits\AcceptanceAssertions;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\PantherTestCaseTrait;

/**
 * Base class for acceptance/e2e tests using Symfony Panther.
 *
 * Features:
 * - Automatic Panther client initialization in setUp()
 * - Real browser automation (Chrome/Firefox via WebDriver)
 * - JavaScript execution support
 * - Screenshot capture on test failure
 * - Inherits all IntegrationTestCase features (factories, helpers, assertions, transactions)
 *
 * Usage:
 * ```php
 * class MyAcceptanceTest extends AbstractAcceptanceTestCase
 * {
 *     public function testCheckout(): void
 *     {
 *         // $this->client is automatically available
 *         $this->client->request('GET', '/');
 *         $this->assertPageContainsText('Welcome');
 *     }
 * }
 * ```
 */
abstract class AbstractAcceptanceTestCase extends AbstractIntegrationTestCase
{
    use PantherTestCaseTrait;
    use AcceptanceAssertions;

    protected Client $client;

    private static bool $driversInstalled = false;

    /**
     * Automatically initialize Panther client before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Install drivers once per test run
        $this->ensureDriversInstalled();

        // Initialize Panther client with default options
        $this->client = static::createPantherClient($this->getBrowserOptions());
    }

    /**
     * Cleanup and capture screenshot on failure.
     */
    protected function tearDown(): void
    {
        // Capture screenshot on test failure for debugging
        if ($this->hasFailed() && isset($this->client)) {
            $this->captureScreenshotOnFailure();
        }

        parent::tearDown();
    }

    /**
     * Get browser configuration options.
     * Override this method to customize browser settings.
     *
     * @return array<string, mixed>
     */
    protected function getBrowserOptions(): array
    {
        $options = [];

        // Headless mode by default (disable with PANTHER_NO_HEADLESS=1)
        if (! getenv('PANTHER_NO_HEADLESS')) {
            $options['capabilities'] = [
                'goog:chromeOptions' => [
                    'args' => [
                        '--headless',
                        '--disable-gpu',
                        '--no-sandbox',
                        '--disable-dev-shm-usage',
                    ],
                ],
            ];
        }

        // Set window size for consistent screenshots
        $options['browser'] = PantherTestCase::CHROME;

        return $options;
    }

    /**
     * Take a screenshot for debugging purposes.
     */
    protected function takeScreenshot(string $name): string
    {
        $screenshotDir = $this->getScreenshotDirectory();

        if (! is_dir($screenshotDir) && (! mkdir($screenshotDir, 0o777, true) && ! is_dir($screenshotDir))) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $screenshotDir));
        }

        $filename = sprintf(
            '%s/%s_%s_%s.png',
            $screenshotDir,
            date('Y-m-d_H-i-s'),
            $this->getTestMethodName(),
            $name
        );

        $this->client->takeScreenshot($filename);

        return $filename;
    }

    /**
     * Wait for an element to be visible.
     */
    protected function waitForElementVisible(string $selector, int $timeout = 5): void
    {
        $this->client->waitFor($selector, $timeout);
    }

    /**
     * Wait for specific text to appear on the page.
     */
    protected function waitForText(string $text, int $timeout = 5): void
    {
        $this->client->waitFor(
            sprintf('//*[contains(text(), "%s")]', $text),
            $timeout
        );
    }

    /**
     * Scroll an element into view.
     */
    protected function scrollToElement(string $selector): void
    {
        $script = sprintf(
            'document.querySelector("%s").scrollIntoView({behavior: "smooth", block: "center"});',
            addslashes($selector)
        );

        $this->executeJavaScript($script);
    }

    /**
     * Execute JavaScript in the browser.
     */
    protected function executeJavaScript(string $script)
    {
        return $this->client->executeScript($script);
    }

    /**
     * Refresh the current page.
     */
    protected function refreshBrowser(): void
    {
        $this->client->reload();
    }

    /**
     * Wait for all AJAX requests to complete.
     * Assumes jQuery is present on the page.
     */
    protected function waitForAjaxComplete(int $timeout = 10): void
    {
        $this->client->waitForInvisibility('body.ajax-loading', $timeout);

        // Also wait for jQuery.active if jQuery is present
        $script = 'return typeof jQuery !== "undefined" ? jQuery.active === 0 : true;';
        $this->client->waitFor(fn () => $this->executeJavaScript($script) === true, $timeout);
    }

    /**
     * Get the screenshot directory path.
     */
    protected function getScreenshotDirectory(): string
    {
        $dir = getenv('PANTHER_SCREENSHOT_DIR');

        if ($dir === false || $dir === '') {
            return dirname(__DIR__, 2) . '/var/screenshots';
        }

        return $dir;
    }

    /**
     * Check if the current test has failed.
     */
    private function hasFailed(): bool
    {
        if ($this->status()->isFailure()) {
            return true;
        }

        return $this->status()->isError();
    }

    /**
     * Capture screenshot on test failure.
     */
    private function captureScreenshotOnFailure(): void
    {
        try {
            $path = $this->takeScreenshot('failure');
            fwrite(STDERR, sprintf("\nScreenshot saved: %s\n", $path));
        } catch (\Throwable $e) {
            fwrite(STDERR, sprintf("\nFailed to capture screenshot: %s\n", $e->getMessage()));
        }
    }

    /**
     * Get the current test method name.
     */
    private function getTestMethodName(): string
    {
        return $this->name();
    }

    /**
     * Ensure browser drivers are installed.
     * Uses dbrekelmans/bdi to automatically detect and install drivers.
     */
    private function ensureDriversInstalled(): void
    {
        if (self::$driversInstalled) {
            return;
        }

        // Check if drivers are already installed
        $chromeDriver = getenv('PANTHER_CHROME_DRIVER_BINARY');

        if ($chromeDriver !== false && file_exists($chromeDriver)) {
            self::$driversInstalled = true;

            return;
        }

        // Try to install drivers using BDI
        $bdiPath = dirname(__DIR__, 2) . '/vendor/bin/bdi';

        if (file_exists($bdiPath)) {
            try {
                $output = shell_exec($bdiPath . ' detect --no-interaction 2>&1');
                fwrite(STDOUT, "\nBrowser drivers installed via BDI\n");

                if ($output) {
                    fwrite(STDOUT, $output . "\n");
                }
            } catch (\Throwable $e) {
                fwrite(STDERR, sprintf("\nWarning: Failed to install drivers via BDI: %s\n", $e->getMessage()));
            }
        }

        self::$driversInstalled = true;
    }
}
