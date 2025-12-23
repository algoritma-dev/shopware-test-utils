<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;
use Shopware\Core\Defaults;

trait TimeHelpers
{
    private static ?\DateTimeImmutable $frozenTime = null;

    private static ?\DateTimeImmutable $originalTime = null;

    /**
     * Freezes time at a specific point.
     */
    protected function freezeTime(\DateTimeInterface $at): void
    {
        self::$originalTime = new \DateTimeImmutable();
        self::$frozenTime = \DateTimeImmutable::createFromInterface($at);
    }

    /**
     * Travels to a specific point in time.
     */
    protected function travelTo(\DateTimeInterface $to): void
    {
        $this->freezeTime($to);
    }

    /**
     * Travels forward in time by a specific interval.
     */
    protected function travelForward(string $interval): void
    {
        $currentTime = $this->getCurrentTime();
        $newTime = $currentTime->modify('+' . $interval);
        $this->travelTo($newTime);
    }

    /**
     * Travels backward in time by a specific interval.
     */
    protected function travelBackward(string $interval): void
    {
        $currentTime = $this->getCurrentTime();
        $newTime = $currentTime->modify('-' . $interval);
        $this->travelTo($newTime);
    }

    /**
     * Unfreezes time and returns to real time.
     */
    protected function travelBack(): void
    {
        self::$frozenTime = null;
        self::$originalTime = null;
    }

    /**
     * Gets the current time (frozen or real).
     */
    protected function getCurrentTime(): \DateTimeImmutable
    {
        return self::$frozenTime ?? new \DateTimeImmutable();
    }

    /**
     * Gets current timestamp.
     */
    protected function getCurrentTimestamp(): int
    {
        return $this->getCurrentTime()->getTimestamp();
    }

    /**
     * Asserts that a timestamp is within a specific range.
     */
    protected function assertTimestampInRange(int $timestamp, int $minTimestamp, int $maxTimestamp): void
    {
        Assert::assertGreaterThanOrEqual(
            $minTimestamp,
            $timestamp,
            sprintf('Timestamp %d is before minimum %d', $timestamp, $minTimestamp)
        );

        Assert::assertLessThanOrEqual(
            $maxTimestamp,
            $timestamp,
            sprintf('Timestamp %d is after maximum %d', $timestamp, $maxTimestamp)
        );
    }

    /**
     * Asserts that a timestamp is recent (within last N seconds).
     */
    protected function assertTimestampIsRecent(int $timestamp, int $withinSeconds = 60): void
    {
        $now = time();
        $diff = abs($now - $timestamp);

        Assert::assertLessThanOrEqual(
            $withinSeconds,
            $diff,
            sprintf('Timestamp %d is not recent (difference: %d seconds)', $timestamp, $diff)
        );
    }

    /**
     * Asserts that a date is in the future.
     */
    protected function assertDateInFuture(\DateTimeInterface $date): void
    {
        $now = $this->getCurrentTime();

        Assert::assertGreaterThan(
            $now->getTimestamp(),
            $date->getTimestamp(),
            'Date is not in the future'
        );
    }

    /**
     * Asserts that a date is in the past.
     */
    protected function assertDateInPast(\DateTimeInterface $date): void
    {
        $now = $this->getCurrentTime();

        Assert::assertLessThan(
            $now->getTimestamp(),
            $date->getTimestamp(),
            'Date is not in the past'
        );
    }

    /**
     * Asserts that two dates are equal (ignoring microseconds).
     */
    protected function assertDatesEqual(\DateTimeInterface $expected, \DateTimeInterface $actual, int $toleranceSeconds = 0): void
    {
        $diff = abs($expected->getTimestamp() - $actual->getTimestamp());

        Assert::assertLessThanOrEqual(
            $toleranceSeconds,
            $diff,
            sprintf(
                'Dates are not equal: expected %s, got %s (difference: %d seconds)',
                $expected->format('Y-m-d H:i:s'),
                $actual->format('Y-m-d H:i:s'),
                $diff
            )
        );
    }

    /**
     * Asserts that a date is between two dates.
     */
    protected function assertDateBetween(\DateTimeInterface $date, \DateTimeInterface $start, \DateTimeInterface $end): void
    {
        Assert::assertGreaterThanOrEqual(
            $start->getTimestamp(),
            $date->getTimestamp(),
            sprintf('Date %s is before start date %s', $date->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s'))
        );

        Assert::assertLessThanOrEqual(
            $end->getTimestamp(),
            $date->getTimestamp(),
            sprintf('Date %s is after end date %s', $date->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'))
        );
    }

    /**
     * Creates a date in the past.
     */
    protected function dateInPast(string $interval): \DateTimeImmutable
    {
        return $this->getCurrentTime()->modify('-' . $interval);
    }

    /**
     * Creates a date in the future.
     */
    protected function dateInFuture(string $interval): \DateTimeImmutable
    {
        return $this->getCurrentTime()->modify('+' . $interval);
    }

    /**
     * Formats a date for Shopware storage.
     */
    protected function formatForStorage(\DateTimeInterface $date): string
    {
        return $date->format(Defaults::STORAGE_DATE_TIME_FORMAT);
    }

    /**
     * Executes a callback with frozen time, then restores.
     */
    protected function withFrozenTime(\DateTimeInterface $at, callable $callback)
    {
        $this->freezeTime($at);

        try {
            return $callback();
        } finally {
            $this->travelBack();
        }
    }
}
