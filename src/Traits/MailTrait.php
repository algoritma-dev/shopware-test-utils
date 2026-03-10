<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Assert;
use Shopware\Core\Framework\Test\TestCaseBase\EventDispatcherBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Email;

trait MailTrait
{
    use KernelTestBehaviour;
    use EventDispatcherBehaviour;

    /**
     * @var array<int, Email>
     */
    private array $capturedEmails = [];

    /**
     * Starts capturing sent emails.
     */
    protected function captureEmails(): void
    {
        $this->clearCapturedEmails();

        // Subscribe to SentMessageEvent to capture sent emails
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $callback = function (SentMessageEvent $event): void {
            $message = $event->getMessage()->getOriginalMessage();

            if ($message instanceof Email) {
                $this->capturedEmails[] = $message;
            }
        };

        $this->addEventListener($dispatcher, SentMessageEvent::class, $callback);
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
    protected function getCapturedEmails(): array
    {
        return $this->capturedEmails;
    }

    /**
     * Gets the last sent email.
     */
    protected function getLastEmail(): ?Email
    {
        if ($this->capturedEmails === []) {
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
            $recipients = $message->getTo();
            foreach ($recipients as $recipient) {
                if ($recipient->getAddress() === $email) {
                    return $message;
                }
            }
        }

        return null;
    }

    /**
     * Assert that a mail template exists for a specific type.
     */
    protected function assertMailTemplateExists(string $typeTechnicalName): void
    {
        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);

        $sql = <<<'SQL'
                SELECT COUNT(*)
                FROM mail_template t
                INNER JOIN mail_template_type type ON t.mail_template_type_id = type.id
                WHERE type.technical_name = :technicalName
            SQL;

        $count = (int) $connection->fetchOne($sql, ['technicalName' => $typeTechnicalName]);

        Assert::assertGreaterThan(0, $count, sprintf('Mail template for type "%s" does not exist.', $typeTechnicalName));
    }

    /**
     * Assert that a mail template subject contains a specific string.
     */
    protected function assertMailTemplateSubjectContains(string $typeTechnicalName, string $locale, string $expectedSubjectPart): void
    {
        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);

        $languageExists = $connection->fetchOne(
            'SELECT 1 FROM language l INNER JOIN locale loc ON l.locale_id = loc.id WHERE loc.code = :locale',
            ['locale' => $locale]
        );

        if (! $languageExists) {
            throw new \RuntimeException(sprintf('Language for locale "%s" does not exist in the database.', $locale));
        }

        $sql = <<<'SQL'
                SELECT trans.subject
                FROM mail_template_translation trans
                INNER JOIN mail_template t ON trans.mail_template_id = t.id
                INNER JOIN mail_template_type type ON t.mail_template_type_id = type.id
                INNER JOIN language l ON trans.language_id = l.id
                INNER JOIN locale loc ON l.locale_id = loc.id
                WHERE type.technical_name = :technicalName
                AND loc.code = :locale
            SQL;

        $subject = $connection->fetchOne($sql, [
            'technicalName' => $typeTechnicalName,
            'locale' => $locale,
        ]);

        Assert::assertNotFalse($subject, sprintf('No translation found for mail template "%s" in locale "%s".', $typeTechnicalName, $locale));
        Assert::assertStringContainsString($expectedSubjectPart, (string) $subject, sprintf('Mail template subject for "%s" (%s) does not contain "%s". Actual: "%s"', $typeTechnicalName, $locale, $expectedSubjectPart, $subject));
    }

    /**
     * Assert that a mail template content contains a specific string.
     */
    protected function assertMailTemplateContentContains(string $typeTechnicalName, string $locale, string $expectedContentPart, bool $html = true): void
    {
        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);

        $languageExists = $connection->fetchOne(
            'SELECT 1 FROM language l INNER JOIN locale loc ON l.locale_id = loc.id WHERE loc.code = :locale',
            ['locale' => $locale]
        );

        if (! $languageExists) {
            throw new \RuntimeException(sprintf('Language for locale "%s" does not exist in the database.', $locale));
        }

        $column = $html ? 'content_html' : 'content_plain';

        $sql = <<<SQL
                SELECT trans.{$column}
                FROM mail_template_translation trans
                INNER JOIN mail_template t ON trans.mail_template_id = t.id
                INNER JOIN mail_template_type type ON t.mail_template_type_id = type.id
                INNER JOIN language l ON trans.language_id = l.id
                INNER JOIN locale loc ON l.locale_id = loc.id
                WHERE type.technical_name = :technicalName
                AND loc.code = :locale
            SQL;

        $content = $connection->fetchOne($sql, [
            'technicalName' => $typeTechnicalName,
            'locale' => $locale,
        ]);

        Assert::assertNotFalse($content, sprintf('No translation found for mail template "%s" in locale "%s".', $typeTechnicalName, $locale));
        Assert::assertStringContainsString($expectedContentPart, (string) $content, sprintf('Mail template content (%s) for "%s" (%s) does not contain "%s".', $html ? 'HTML' : 'Plain', $typeTechnicalName, $locale, $expectedContentPart));
    }
}
