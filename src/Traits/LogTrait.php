<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Assert;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

trait LogTrait
{
    use KernelTestBehaviour;

    private array $capturedLogs = [];

    private array $logHandlers = [];

    /**
     * Starts capturing logs from a specific channel.
     */
    protected function captureLog(string $channel = 'app'): void
    {
        $logger = $this->getLogger($channel);

        if (! $logger instanceof Logger) {
            throw new \RuntimeException("Logger for channel '{$channel}' is not an instance of Monolog\\Logger");
        }

        $handler = new TestHandler();
        $logger->pushHandler($handler);

        $this->logHandlers[$channel] = $handler;
        $this->capturedLogs[$channel] = [];
    }

    /**
     * Asserts that a log contains a specific message.
     */
    protected function assertLogContains(string $message, string $level = LogLevel::INFO, string $channel = 'app'): void
    {
        $logs = $this->getCapturedLogs($channel);
        $found = false;

        foreach ($logs as $log) {
            if ($log['level_name'] === strtoupper($level) && str_contains((string) $log['message'], $message)) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue(
            $found,
            sprintf('Log message "%s" with level "%s" not found in channel "%s"', $message, $level, $channel)
        );
    }

    /**
     * Asserts that no error logs were captured.
     */
    protected function assertNoErrors(string $channel = 'app'): void
    {
        $logs = $this->getCapturedLogs($channel);
        $errors = array_filter($logs, fn (array $log): bool => in_array($log['level_name'], ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']));

        Assert::assertEmpty(
            $errors,
            sprintf('Found %d error logs in channel "%s": %s', count($errors), $channel, json_encode($errors))
        );
    }

    /**
     * Asserts that no warning logs were captured.
     */
    protected function assertNoWarnings(string $channel = 'app'): void
    {
        $logs = $this->getCapturedLogs($channel);
        $warnings = array_filter($logs, fn (array $log): bool => $log['level_name'] === 'WARNING');

        Assert::assertEmpty(
            $warnings,
            sprintf('Found %d warning logs in channel "%s": %s', count($warnings), $channel, json_encode($warnings))
        );
    }

    /**
     * Asserts that a specific log level was logged.
     */
    protected function assertLogLevel(string $level, string $channel = 'app'): void
    {
        $logs = $this->getCapturedLogs($channel);
        $found = false;

        foreach ($logs as $log) {
            if ($log['level_name'] === strtoupper($level)) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue(
            $found,
            sprintf('No logs with level "%s" found in channel "%s"', $level, $channel)
        );
    }

    /**
     * Asserts the count of log messages.
     */
    protected function assertLogCount(int $expectedCount, ?string $level = null, string $channel = 'app'): void
    {
        $logs = $this->getCapturedLogs($channel);

        if ($level !== null) {
            $logs = array_filter($logs, fn (array $log): bool => $log['level_name'] === strtoupper($level));
        }

        $actualCount = count($logs);

        Assert::assertEquals(
            $expectedCount,
            $actualCount,
            sprintf('Expected %d logs, found %d in channel "%s"', $expectedCount, $actualCount, $channel)
        );
    }

    /**
     * Gets all captured log messages.
     */
    protected function getCapturedLogs(string $channel = 'app'): array
    {
        if (! isset($this->logHandlers[$channel])) {
            return [];
        }

        /** @var TestHandler $handler */
        $handler = $this->logHandlers[$channel];

        return $handler->getRecords();
    }

    /**
     * Gets logged messages (without context).
     */
    protected function getLoggedMessages(string $channel = 'app', ?string $level = null): array
    {
        $logs = $this->getCapturedLogs($channel);
        $messages = [];

        foreach ($logs as $log) {
            if ($level === null || $log['level_name'] === strtoupper($level)) {
                $messages[] = $log['message'];
            }
        }

        return $messages;
    }

    /**
     * Clears captured logs.
     */
    protected function clearCapturedLogs(string $channel = 'app'): void
    {
        if (isset($this->logHandlers[$channel])) {
            /** @var TestHandler $handler */
            $handler = $this->logHandlers[$channel];
            $handler->clear();
        }
    }

    /**
     * Asserts that logs match a pattern.
     */
    protected function assertLogMatchesPattern(string $pattern, string $level = LogLevel::INFO, string $channel = 'app'): void
    {
        $logs = $this->getCapturedLogs($channel);
        $found = false;

        foreach ($logs as $log) {
            if ($log['level_name'] === strtoupper($level) && preg_match($pattern, (string) $log['message'])) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue(
            $found,
            sprintf('No log matching pattern "%s" with level "%s" found in channel "%s"', $pattern, $level, $channel)
        );
    }

    /**
     * Asserts that context contains specific data.
     */
    protected function assertLogContextContains(string $key, $expectedValue, string $channel = 'app'): void
    {
        $logs = $this->getCapturedLogs($channel);
        $found = false;

        foreach ($logs as $log) {
            if (isset($log['context'][$key]) && $log['context'][$key] === $expectedValue) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue(
            $found,
            sprintf('No log with context key "%s" = "%s" found in channel "%s"', $key, $expectedValue, $channel)
        );
    }

    /**
     * Gets the logger for a specific channel.
     */
    private function getLogger(string $channel): LoggerInterface
    {
        $serviceName = $channel === 'app' ? 'logger' : 'monolog.logger.' . $channel;

        return $this->getContainer()->get($serviceName);
    }
}
