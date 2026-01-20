<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Traits\EventTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EventHelpersTest extends TestCase
{
    use EventTrait;

    private Stub&ContainerInterface $container;

    private MockObject&EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        $this->container = $this->createStub(ContainerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->container->method('get')->willReturn($this->dispatcher);
    }

    public function testCatchEvent(): void
    {
        $this->dispatcher->expects($this->once())->method('addListener');

        $this->catchEvent(Event::class);
    }

    public function testAssertEventDispatched(): void
    {
        // Simulate caught event
        $this->caughtEvents[Event::class][] = new Event();

        $this->assertEventDispatched(Event::class);
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
