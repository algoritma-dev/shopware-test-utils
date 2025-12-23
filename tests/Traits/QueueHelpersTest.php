<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Traits\QueueHelpers;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\TraceableMessageBus;

class QueueHelpersTest extends TestCase
{
    use QueueHelpers;

    private MockObject $container;

    private MockObject $bus;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->bus = $this->createMock(TraceableMessageBus::class);

        $this->container->method('get')->willReturn($this->bus);
    }

    public function testAssertMessageQueued(): void
    {
        $message = new \stdClass();
        $envelope = ['message' => $message];

        $this->bus->method('getDispatchedMessages')->willReturn([$envelope]);

        $this->assertMessageQueued(\stdClass::class, 1);
    }

    public function testAssertQueueEmpty(): void
    {
        $this->bus->method('getDispatchedMessages')->willReturn([]);

        $this->assertQueueEmpty();
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
