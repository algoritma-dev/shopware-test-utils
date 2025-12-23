<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\Defaults;

/**
 * Helper for time manipulation and date utilities in tests.
 */
class TimeHelper
{
    private static ?\DateTimeImmutable $frozenTime = null;

    private static ?\DateTimeImmutable $originalTime = null;

    /**
     * Freezes time at a specific point.
     */
    public function freezeTime(\DateTimeInterface $at): void
    {
        self::$originalTime = new \DateTimeImmutable();
        self::$frozenTime = \DateTimeImmutable::createFromInterface($at);
    }

    /**
     * Travels to a specific point in time.
     */
    public function travelTo(\DateTimeInterface $to): void
    {
        $this->freezeTime($to);
    }

    /**
     * Travels forward in time by a specific interval.
     */
    public function travelForward(string $interval): void
    {
        $currentTime = $this->getCurrentTime();
        $newTime = $currentTime->modify('+' . $interval);
        $this->travelTo($newTime);
    }

    /**
     * Travels backward in time by a specific interval.
     */
    public function travelBackward(string $interval): void
    {
        $currentTime = $this->getCurrentTime();
        $newTime = $currentTime->modify('-' . $interval);
        $this->travelTo($newTime);
    }

    /**
     * Unfreezes time and returns to real time.
     */
    public function travelBack(): void
    {
        self::$frozenTime = null;
        self::$originalTime = null;
    }

    /**
     * Gets the current time (frozen or real).
     */
    public function getCurrentTime(): \DateTimeImmutable
    {
        return self::$frozenTime ?? new \DateTimeImmutable();
    }

    /**
     * Gets current timestamp.
     */
    public function getCurrentTimestamp(): int
    {
        return $this->getCurrentTime()->getTimestamp();
    }

    /**
     * Creates a date in the past.
     */
    public function dateInPast(string $interval): \DateTimeImmutable
    {
        return $this->getCurrentTime()->modify('-' . $interval);
    }

    /**
     * Creates a date in the future.
     */
    public function dateInFuture(string $interval): \DateTimeImmutable
    {
        return $this->getCurrentTime()->modify('+' . $interval);
    }

    /**
     * Formats a date for Shopware storage.
     */
    public function formatForStorage(\DateTimeInterface $date): string
    {
        return $date->format(Defaults::STORAGE_DATE_TIME_FORMAT);
    }

    /**
     * Executes a callback with frozen time, then restores.
     */
    public function withFrozenTime(\DateTimeInterface $at, callable $callback)
    {
        $this->freezeTime($at);

        try {
            return $callback();
        } finally {
            $this->travelBack();
        }
    }
}
