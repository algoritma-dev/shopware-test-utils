<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;

/**
 * Trait for time-related assertions in tests.
 */
trait TimeHelpers
{
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
        $now = new \DateTimeImmutable();

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
        $now = new \DateTimeImmutable();

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
}
