<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;
use Shopware\Core\Framework\Test\TestCaseBase\EventDispatcherBehaviour;
use Symfony\Contracts\EventDispatcher\Event;

trait EventHelpers
{
    use EventDispatcherBehaviour;

    /**
     * @var Event
     */
    private array $caughtEvents = [];

    /**
     * Starts capturing the given event.
     * This method registers a listener that records all dispatched events of the given type.
     */
    protected function catchEvent(string $eventClass): void
    {
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $callback = function (Event $event) use ($eventClass): void {
            $this->caughtEvents[$eventClass][] = $event;
        };

        $this->addEventListener($dispatcher, $eventClass, $callback);
    }

    protected function assertEventDispatched(string $eventClass, ?callable $callback = null): void
    {
        Assert::assertArrayHasKey($eventClass, $this->caughtEvents, sprintf('Event %s was not dispatched (or not caught). Did you call catchEvent()?', $eventClass));
        Assert::assertNotEmpty($this->caughtEvents[$eventClass], sprintf('Event %s was not dispatched.', $eventClass));

        if ($callback) {
            foreach ($this->caughtEvents[$eventClass] as $event) {
                if ($callback($event) === true) {
                    return;
                }
            }
            Assert::fail(sprintf('Event %s was dispatched but did not pass the callback validation.', $eventClass));
        }
    }

    protected function assertEventNotDispatched(string $eventClass): void
    {
        if (! array_key_exists($eventClass, $this->caughtEvents)) {
            return;
        }
        Assert::assertEmpty($this->caughtEvents[$eventClass], sprintf('Event %s was dispatched unexpectedly.', $eventClass));
    }
}
