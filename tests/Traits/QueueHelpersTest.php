<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Traits\QueueHelpers;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\TraceableMessageBus;

class QueueHelpersTest extends TestCase
{
    use QueueHelpers;

    private static Stub&ContainerInterface $container;

    private Stub&TraceableMessageBus $bus;

    protected function setUp(): void
    {
        self::$container = $this->createStub(ContainerInterface::class);
        $this->bus = $this->createStub(TraceableMessageBus::class);

        self::$container->method('get')->willReturn($this->bus);
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

    protected static function getContainer(): ContainerInterface
    {
        return self::$container;
    }
}
