<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;
use Symfony\Component\Messenger\TraceableMessageBus;

trait QueueHelpers
{
    protected function getTraceableBus(): TraceableMessageBus
    {
        $bus = $this->getContainer()->get('messenger.bus.test_shopware');

        if (! $bus instanceof TraceableMessageBus) {
            // Fallback or error if not traceable
            throw new \RuntimeException('The test bus is not traceable. Ensure you are running in a test environment with debug enabled.');
        }

        return $bus;
    }

    protected function assertMessageQueued(string $messageClass, int $count = 1): void
    {
        $bus = $this->getTraceableBus();
        $dispatched = $bus->getDispatchedMessages();

        $found = 0;
        foreach ($dispatched as $envelope) {
            if ($messageClass === $envelope['message']::class) {
                ++$found;
            }
        }

        Assert::assertEquals($count, $found, sprintf('Expected %d messages of type %s to be queued, found %d.', $count, $messageClass, $found));
    }

    protected function assertQueueEmpty(): void
    {
        $bus = $this->getTraceableBus();
        Assert::assertCount(0, $bus->getDispatchedMessages(), 'Queue is not empty.');
    }
}
