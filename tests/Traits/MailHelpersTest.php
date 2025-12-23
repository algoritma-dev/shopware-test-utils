<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Traits\MailHelpers;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailHelpersTest extends TestCase
{
    use MailHelpers;

    private static Stub $container;

    private MockObject $dispatcher;

    protected function setUp(): void
    {
        self::$container = $this->createStub(ContainerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        self::$container->method('get')->willReturn($this->dispatcher);
    }

    public function testCaptureEmails(): void
    {
        $this->dispatcher->expects($this->once())->method('addListener');

        $this->captureEmails();
    }

    public function testAssertMailSent(): void
    {
        $email = new Email();
        $this->capturedEmails[] = $email;

        $this->assertMailSent(1);
    }

    public function testAssertMailSentTo(): void
    {
        $email = new Email();
        $email->to(new Address('test@example.com'));
        $this->capturedEmails[] = $email;

        $this->assertMailSentTo('test@example.com');
    }

    protected static function getContainer(): ContainerInterface
    {
        return self::$container;
    }
}
