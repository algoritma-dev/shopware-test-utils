<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;
use Symfony\Contracts\EventDispatcher\Event;

trait EventHelpers
{
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

        // Use Shopware's EventDispatcherBehaviour to register the listener if available.
        // This ensures the listener is automatically removed after the test.
        if (method_exists($this, 'addEventListener')) {
            // Call EventDispatcherBehaviour::addEventListener
            // Signature: addEventListener(EventDispatcherInterface $dispatcher, string $eventName, callable $callback, int $priority = 0, bool $once = false)
            $this->addEventListener($dispatcher, $eventClass, $callback);
        } else {
            // Fallback if behaviour is not used
            $dispatcher->addListener($eventClass, $callback);
        }
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
