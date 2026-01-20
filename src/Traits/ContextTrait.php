<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Algoritma\ShopwareTestUtils\Factory\ContextFactory;
use PHPUnit\Framework\Assert;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait ContextTrait
{
    use KernelTestBehaviour;

    /**
     * Gets the ContextFactory instance.
     */
    protected function getContextFactory(): ContextFactory
    {
        /** @var ContextFactory $factory */
        $factory = self::getContainer()->get(ContextFactory::class);

        return $factory;
    }

    /**
     * Creates a default CLI context.
     */
    protected function createDefaultContext(): Context
    {
        return $this->getContextFactory()->createDefaultContext();
    }

    /**
     * Creates a sales channel context.
     *
     * @param array<string, mixed> $options
     */
    protected function createSalesChannelContextObject(?string $salesChannelId = null, array $options = []): SalesChannelContext
    {
        return $this->getContextFactory()->createSalesChannelContext($salesChannelId, $options);
    }

    /**
     * Asserts that a context is valid.
     */
    protected function assertContextIsValid(Context $context): void
    {
        Assert::assertNotNull($context, 'Context should not be null');
        Assert::assertIsString($context->getVersionId(), 'Context should have a version ID');
    }

    /**
     * Asserts that a sales channel context is valid.
     */
    protected function assertSalesChannelContextIsValid(SalesChannelContext $context): void
    {
        Assert::assertNotNull($context, 'SalesChannelContext should not be null');
        Assert::assertNotNull($context->getSalesChannel(), 'SalesChannel should not be null');
        Assert::assertNotNull($context->getSalesChannelId(), 'SalesChannelId should not be null');
    }
}
