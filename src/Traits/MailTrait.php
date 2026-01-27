<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;
use Shopware\Core\Framework\Test\TestCaseBase\EventDispatcherBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

trait MailTrait
{
    use KernelTestBehaviour;
    use EventDispatcherBehaviour;

    /**
     * @var array<int, RawMessage>
     */
    private array $capturedEmails = [];

    /**
     * Starts capturing sent emails.
     */
    protected function captureEmails(): void
    {
        $this->capturedEmails = [];

        // Subscribe to MessageEvent to capture emails
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $callback = function (MessageEvent $event): void {
            $this->capturedEmails[] = $event->getMessage();
        };

        $this->addEventListener($dispatcher, MessageEvent::class, $callback);
    }

    /**
     * Asserts that a specific number of emails were sent.
     */
    protected function assertMailSent(int $count = 1): void
    {
        $actualCount = count($this->capturedEmails);

        Assert::assertEquals(
            $count,
            $actualCount,
            sprintf('Expected %d emails to be sent, but %d were sent', $count, $actualCount)
        );
    }

    /**
     * Asserts that at least one email was sent.
     */
    protected function assertMailWasSent(): void
    {
        Assert::assertGreaterThan(
            0,
            count($this->capturedEmails),
            'Expected at least one email to be sent, but none were sent'
        );
    }

    /**
     * Asserts that no emails were sent.
     */
    protected function assertNoMailSent(): void
    {
        Assert::assertEmpty(
            $this->capturedEmails,
            sprintf('Expected no emails to be sent, but %d were sent', count($this->capturedEmails))
        );
    }

    /**
     * Asserts that an email was sent to a specific recipient.
     */
    protected function assertMailSentTo(string $email): void
    {
        $found = false;

        foreach ($this->capturedEmails as $message) {
            if (! $message instanceof Email) {
                continue;
            }

            $recipients = $message->getTo();
            foreach ($recipients as $recipient) {
                if ($recipient->getAddress() === $email) {
                    $found = true;
                    break 2;
                }
            }
        }

        Assert::assertTrue(
            $found,
            sprintf('No email was sent to "%s"', $email)
        );
    }

    /**
     * Asserts that an email with a specific subject was sent.
     */
    protected function assertMailWithSubject(string $subject): void
    {
        $found = false;

        foreach ($this->capturedEmails as $message) {
            if (! $message instanceof Email) {
                continue;
            }

            if ($message->getSubject() === $subject) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue(
            $found,
            sprintf('No email with subject "%s" was sent', $subject)
        );
    }

    /**
     * Asserts that an email contains specific text in the body.
     */
    protected function assertMailContains(string $text): void
    {
        $found = false;

        foreach ($this->capturedEmails as $message) {
            if (! $message instanceof Email) {
                continue;
            }

            $body = $message->getHtmlBody() ?? $message->getTextBody() ?? '';

            if (str_contains($body, $text)) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue(
            $found,
            sprintf('No email contains the text "%s"', $text)
        );
    }

    /**
     * Gets all captured emails.
     *
     * @return array<int, RawMessage>
     */
    protected function getCapturedEmails(): array
    {
        return $this->capturedEmails;
    }

    /**
     * Gets the last sent email.
     */
    protected function getLastEmail(): ?Email
    {
        if (empty($this->capturedEmails)) {
            return null;
        }

        $lastMessage = end($this->capturedEmails);

        return $lastMessage instanceof Email ? $lastMessage : null;
    }

    /**
     * Clears captured emails.
     */
    protected function clearCapturedEmails(): void
    {
        $this->capturedEmails = [];
    }

    /**
     * Asserts that an email was sent from a specific sender.
     */
    protected function assertMailSentFrom(string $email): void
    {
        $found = false;

        foreach ($this->capturedEmails as $message) {
            if (! $message instanceof Email) {
                continue;
            }

            $from = $message->getFrom();
            foreach ($from as $sender) {
                if ($sender->getAddress() === $email) {
                    $found = true;
                    break 2;
                }
            }
        }

        Assert::assertTrue(
            $found,
            sprintf('No email was sent from "%s"', $email)
        );
    }

    /**
     * Asserts that an email has a specific attachment.
     */
    protected function assertMailHasAttachment(string $filename): void
    {
        $found = false;

        foreach ($this->capturedEmails as $message) {
            if (! $message instanceof Email) {
                continue;
            }

            foreach ($message->getAttachments() as $attachment) {
                if ($attachment->getFilename() === $filename) {
                    $found = true;
                    break 2;
                }
            }
        }

        Assert::assertTrue(
            $found,
            sprintf('No email has attachment "%s"', $filename)
        );
    }

    /**
     * Finds an email by recipient.
     */
    protected function findEmailByRecipient(string $email): ?Email
    {
        foreach ($this->capturedEmails as $message) {
            if (! $message instanceof Email) {
                continue;
            }

            $recipients = $message->getTo();
            foreach ($recipients as $recipient) {
                if ($recipient->getAddress() === $email) {
                    return $message;
                }
            }
        }

        return null;
    }
}
