<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for sales channel-related operations and assertions.
 */
class SalesChannelHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    // --- Sales Channel Assertions ---

    /**
     * Assert that a sales channel is active.
     */
    public function assertSalesChannelActive(string $salesChannelId): void
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('sales_channel.repository');
        $context = Context::createCLIContext();

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $repository->search(new Criteria([$salesChannelId]), $context)->first();
        assert($salesChannel !== null, sprintf('Sales channel %s not found', $salesChannelId));
        assert($salesChannel->getActive(), sprintf('Sales channel %s is not active', $salesChannelId));
    }

    /**
     * Assert that the context uses a specific currency.
     */
    public function assertContextCurrency(SalesChannelContext $context, string $currencyId): void
    {
        $actualCurrencyId = $context->getCurrency()->getId();
        assert($actualCurrencyId === $currencyId, sprintf('Context currency is %s, expected %s', $actualCurrencyId, $currencyId));
    }

    /**
     * Assert that the context uses a specific language.
     */
    public function assertContextLanguage(SalesChannelContext $context, string $languageId): void
    {
        $actualLanguageId = $context->getLanguageId();
        assert($actualLanguageId === $languageId, sprintf('Context language is %s, expected %s', $actualLanguageId, $languageId));
    }
}
