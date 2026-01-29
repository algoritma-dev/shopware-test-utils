<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Traits\MailTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailHelpersTest extends TestCase
{
    use MailTrait;

    private static Stub&ContainerInterface $container;

    private MockObject&EventDispatcherInterface $dispatcher;

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
        $email->from(new Address('from@example.com'));
        $email->to(new Address('test@example.com'));
        $email->text('test body');
        $this->captureEmail($email);

        $this->assertMailSent(1);
    }

    public function testAssertMailSentTo(): void
    {
        $email = new Email();
        $email->to(new Address('test@example.com'));
        $email->from(new Address('from@example.com'));
        $email->text('test body');
        $this->captureEmail($email);

        $this->assertMailSentTo('test@example.com');
    }

    protected static function getContainer(): ContainerInterface
    {
        return self::$container;
    }

    private function captureEmail(Email $email): void
    {
        $sentMessage = new SentMessage($email, Envelope::create($email));
        $event = new SentMessageEvent($sentMessage);

        $this->getMailCaptureHelper()->captureFromEvent($event);
    }
}
