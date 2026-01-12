<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\MailHelper;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MailHelperTest extends TestCase
{
    private MockObject $container;

    private MockObject $connection;

    private MailHelper $helper;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->container->method('get')
            ->with(Connection::class)
            ->willReturn($this->connection);
        $this->helper = new MailHelper($this->container);
    }

    public function testAssertMailTemplateExistsPassesWhenTemplateExists(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1);

        $this->helper->assertMailTemplateExists('order_confirmation');

        $this->assertTrue(true); // If no exception, assertion passed
    }

    public function testAssertMailTemplateExistsFailsWhenTemplateDoesNotExist(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchOne')
            ->willReturn(0);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Mail template for type "order_confirmation" does not exist');

        $this->helper->assertMailTemplateExists('order_confirmation');
    }

    public function testAssertMailTemplateSubjectContainsPassesWhenSubjectContainsString(): void
    {
        $this->connection->expects($this->exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                1, // Language exists
                'Your order confirmation - Order #12345' // Subject
            );

        $this->helper->assertMailTemplateSubjectContains('order_confirmation', 'en-GB', 'order confirmation');

        $this->assertTrue(true); // If no exception, assertion passed
    }

    public function testAssertMailTemplateSubjectContainsFailsWhenSubjectDoesNotContainString(): void
    {
        $this->connection->expects($this->exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                1, // Language exists
                'Your order confirmation' // Subject
            );

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('does not contain "invoice"');

        $this->helper->assertMailTemplateSubjectContains('order_confirmation', 'en-GB', 'invoice');
    }

    public function testAssertMailTemplateSubjectContainsThrowsExceptionWhenLanguageDoesNotExist(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchOne')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Language for locale "invalid-locale" does not exist');

        $this->helper->assertMailTemplateSubjectContains('order_confirmation', 'invalid-locale', 'test');
    }

    public function testAssertMailTemplateSubjectContainsFailsWhenNoTranslationFound(): void
    {
        $this->connection->expects($this->exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                1, // Language exists
                false // No translation
            );

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('No translation found for mail template');

        $this->helper->assertMailTemplateSubjectContains('order_confirmation', 'en-GB', 'test');
    }

    public function testAssertMailTemplateContentContainsPassesForHtmlContent(): void
    {
        $this->connection->expects($this->exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                1, // Language exists
                '<html><body>Your order is confirmed</body></html>' // HTML content
            );

        $this->helper->assertMailTemplateContentContains('order_confirmation', 'en-GB', 'order is confirmed', true);

        $this->assertTrue(true); // If no exception, assertion passed
    }

    public function testAssertMailTemplateContentContainsPassesForPlainContent(): void
    {
        $this->connection->expects($this->exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                1, // Language exists
                'Your order is confirmed' // Plain content
            );

        $this->helper->assertMailTemplateContentContains('order_confirmation', 'en-GB', 'order is confirmed', false);

        $this->assertTrue(true); // If no exception, assertion passed
    }

    public function testAssertMailTemplateContentContainsFailsWhenContentDoesNotContainString(): void
    {
        $this->connection->expects($this->exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                1, // Language exists
                '<html><body>Your order is confirmed</body></html>' // HTML content
            );

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('does not contain "invoice"');

        $this->helper->assertMailTemplateContentContains('order_confirmation', 'en-GB', 'invoice', true);
    }

    public function testAssertMailTemplateContentContainsThrowsExceptionWhenLanguageDoesNotExist(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchOne')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Language for locale "invalid-locale" does not exist');

        $this->helper->assertMailTemplateContentContains('order_confirmation', 'invalid-locale', 'test', true);
    }

    public function testAssertMailTemplateContentContainsFailsWhenNoTranslationFound(): void
    {
        $this->connection->expects($this->exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                1, // Language exists
                false // No translation
            );

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('No translation found for mail template');

        $this->helper->assertMailTemplateContentContains('order_confirmation', 'en-GB', 'test', true);
    }
}
