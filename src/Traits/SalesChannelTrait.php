<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

/**
 * Trait for sales channel-related operations and assertions.
 */
trait SalesChannelTrait
{
    use KernelTestBehaviour;

    /**
     * Assert that a sales channel is active.
     */
    protected function assertSalesChannelActive(string $salesChannelId): void
    {
        /** @var EntityRepository<SalesChannelCollection> $repository */
        $repository = static::getContainer()->get('sales_channel.repository');
        $context = Context::createCLIContext();

        $entity = $repository->search(new Criteria([$salesChannelId]), $context)->first();
        $salesChannel = $entity instanceof SalesChannelEntity ? $entity : null;
        assert($salesChannel instanceof SalesChannelEntity, sprintf('Sales channel %s not found', $salesChannelId));
        assert($salesChannel->getActive(), sprintf('Sales channel %s is not active', $salesChannelId));
    }

    /**
     * Assert that the context uses a specific currency.
     */
    protected function assertContextCurrency(SalesChannelContext $context, string $currencyId): void
    {
        $actualCurrencyId = $context->getCurrency()->getId();
        assert($actualCurrencyId === $currencyId, sprintf('Context currency is %s, expected %s', $actualCurrencyId, $currencyId));
    }

    /**
     * Assert that the context uses a specific language.
     */
    protected function assertContextLanguage(SalesChannelContext $context, string $languageId): void
    {
        $actualLanguageId = $context->getLanguageId();
        assert($actualLanguageId === $languageId, sprintf('Context language is %s, expected %s', $actualLanguageId, $languageId));
    }
}
