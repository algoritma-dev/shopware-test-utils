<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for mail template operations and assertions.
 */
class MailHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    // --- Mail Template Assertions ---

    /**
     * Assert that a mail template exists for a specific type.
     */
    public function assertMailTemplateExists(string $typeTechnicalName): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $sql = <<<'SQL'
                SELECT COUNT(*)
                FROM mail_template t
                INNER JOIN mail_template_type type ON t.mail_template_type_id = type.id
                WHERE type.technical_name = :technicalName
            SQL;

        $count = (int) $connection->fetchOne($sql, ['technicalName' => $typeTechnicalName]);

        assert($count > 0, sprintf('Mail template for type "%s" does not exist.', $typeTechnicalName));
    }

    /**
     * Assert that a mail template subject contains a specific string.
     */
    public function assertMailTemplateSubjectContains(string $typeTechnicalName, string $locale, string $expectedSubjectPart): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

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

        assert($subject !== false, sprintf('No translation found for mail template "%s" in locale "%s".', $typeTechnicalName, $locale));
        assert(str_contains((string) $subject, $expectedSubjectPart), sprintf('Mail template subject for "%s" (%s) does not contain "%s". Actual: "%s"', $typeTechnicalName, $locale, $expectedSubjectPart, $subject));
    }

    /**
     * Assert that a mail template content contains a specific string.
     */
    public function assertMailTemplateContentContains(string $typeTechnicalName, string $locale, string $expectedContentPart, bool $html = true): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

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

        assert($content !== false, sprintf('No translation found for mail template "%s" in locale "%s".', $typeTechnicalName, $locale));
        assert(str_contains((string) $content, $expectedContentPart), sprintf('Mail template content (%s) for "%s" (%s) does not contain "%s".', $html ? 'HTML' : 'Plain', $typeTechnicalName, $locale, $expectedContentPart));
    }
}
