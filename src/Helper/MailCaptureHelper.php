<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use PHPUnit\Framework\Assert;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Email;

/**
 * Helper for capturing and asserting sent emails.
 */
class MailCaptureHelper
{
    /**
     * @var array<int, Email>
     */
    private array $capturedEmails = [];

    /**
     * Capture an email from a mailer sent event.
     */
    public function captureFromEvent(SentMessageEvent $event): void
    {
        $message = $event->getMessage()->getOriginalMessage();

        if ($message instanceof Email) {
            $this->capturedEmails[] = $message;
        }
    }

    /**
     * Clears captured emails.
     */
    public function clearCapturedEmails(): void
    {
        $this->capturedEmails = [];
    }

    /**
     * Asserts that a specific number of emails were sent.
     */
    public function assertMailSent(int $count = 1): void
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
    public function assertMailWasSent(): void
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
    public function assertNoMailSent(): void
    {
        Assert::assertEmpty(
            $this->capturedEmails,
            sprintf('Expected no emails to be sent, but %d were sent', count($this->capturedEmails))
        );
    }

    /**
     * Asserts that an email was sent to a specific recipient.
     */
    public function assertMailSentTo(string $email): void
    {
        $found = false;

        foreach ($this->capturedEmails as $message) {
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
    public function assertMailWithSubject(string $subject): void
    {
        $found = false;

        foreach ($this->capturedEmails as $message) {
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
    public function assertMailContains(string $text): void
    {
        $found = false;

        foreach ($this->capturedEmails as $message) {
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
     * @return array<int, Email>
     */
    public function getCapturedEmails(): array
    {
        return $this->capturedEmails;
    }

    /**
     * Gets the last sent email.
     */
    public function getLastEmail(): ?Email
    {
        if ($this->capturedEmails === []) {
            return null;
        }

        $lastMessage = end($this->capturedEmails);

        return $lastMessage instanceof Email ? $lastMessage : null;
    }

    /**
     * Asserts that an email was sent from a specific sender.
     */
    public function assertMailSentFrom(string $email): void
    {
        $found = false;

        foreach ($this->capturedEmails as $message) {
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
    public function assertMailHasAttachment(string $filename): void
    {
        $found = false;

        foreach ($this->capturedEmails as $message) {
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
    public function findEmailByRecipient(string $email): ?Email
    {
        foreach ($this->capturedEmails as $message) {
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
