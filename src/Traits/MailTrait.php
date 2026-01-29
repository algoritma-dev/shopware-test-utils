<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Algoritma\ShopwareTestUtils\Helper\MailCaptureHelper;
use Shopware\Core\Framework\Test\TestCaseBase\EventDispatcherBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Email;

trait MailTrait
{
    use KernelTestBehaviour;
    use EventDispatcherBehaviour;

    private ?MailCaptureHelper $mailCaptureHelper = null;

    protected function getMailCaptureHelper(): MailCaptureHelper
    {
        if (! $this->mailCaptureHelper instanceof MailCaptureHelper) {
            $this->mailCaptureHelper = new MailCaptureHelper();
        }

        return $this->mailCaptureHelper;
    }

    /**
     * Starts capturing sent emails.
     */
    protected function captureEmails(): void
    {
        $this->getMailCaptureHelper()->clearCapturedEmails();

        // Subscribe to SentMessageEvent to capture sent emails
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $helper = $this->getMailCaptureHelper();
        $callback = static function (SentMessageEvent $event) use ($helper): void {
            $helper->captureFromEvent($event);
        };

        $this->addEventListener($dispatcher, SentMessageEvent::class, $callback);
    }

    /**
     * Asserts that a specific number of emails were sent.
     */
    protected function assertMailSent(int $count = 1): void
    {
        $this->getMailCaptureHelper()->assertMailSent($count);
    }

    /**
     * Asserts that at least one email was sent.
     */
    protected function assertMailWasSent(): void
    {
        $this->getMailCaptureHelper()->assertMailWasSent();
    }

    /**
     * Asserts that no emails were sent.
     */
    protected function assertNoMailSent(): void
    {
        $this->getMailCaptureHelper()->assertNoMailSent();
    }

    /**
     * Asserts that an email was sent to a specific recipient.
     */
    protected function assertMailSentTo(string $email): void
    {
        $this->getMailCaptureHelper()->assertMailSentTo($email);
    }

    /**
     * Asserts that an email with a specific subject was sent.
     */
    protected function assertMailWithSubject(string $subject): void
    {
        $this->getMailCaptureHelper()->assertMailWithSubject($subject);
    }

    /**
     * Asserts that an email contains specific text in the body.
     */
    protected function assertMailContains(string $text): void
    {
        $this->getMailCaptureHelper()->assertMailContains($text);
    }

    /**
     * Gets all captured emails.
     *
     * @return array<int, Email>
     */
    protected function getCapturedEmails(): array
    {
        return $this->getMailCaptureHelper()->getCapturedEmails();
    }

    /**
     * Gets the last sent email.
     */
    protected function getLastEmail(): ?Email
    {
        return $this->getMailCaptureHelper()->getLastEmail();
    }

    /**
     * Clears captured emails.
     */
    protected function clearCapturedEmails(): void
    {
        $this->getMailCaptureHelper()->clearCapturedEmails();
    }

    /**
     * Asserts that an email was sent from a specific sender.
     */
    protected function assertMailSentFrom(string $email): void
    {
        $this->getMailCaptureHelper()->assertMailSentFrom($email);
    }

    /**
     * Asserts that an email has a specific attachment.
     */
    protected function assertMailHasAttachment(string $filename): void
    {
        $this->getMailCaptureHelper()->assertMailHasAttachment($filename);
    }

    /**
     * Finds an email by recipient.
     */
    protected function findEmailByRecipient(string $email): ?Email
    {
        return $this->getMailCaptureHelper()->findEmailByRecipient($email);
    }
}
