<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;
use Shopware\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Symfony\Component\Messenger\TraceableMessageBus;

trait QueueTrait
{
    use QueueTestBehaviour;

    protected function getTraceableBus(): TraceableMessageBus
    {
        $bus = $this->getContainer()->get('messenger.bus.test_shopware');

        if (! $bus instanceof TraceableMessageBus) {
            // Fallback or error if not traceable
            throw new \RuntimeException('The test bus is not traceable. Ensure you are running in a test environment with debug enabled.');
        }

        return $bus;
    }
}
