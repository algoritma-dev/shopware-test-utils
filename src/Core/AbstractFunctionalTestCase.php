<?php

namespace Algoritma\ShopwareTestUtils\Core;

use Algoritma\ShopwareTestUtils\Traits\StorefrontApiRequestTrait;
use Algoritma\ShopwareTestUtils\Traits\StorefrontRequestTrait;
use PHPUnit\Framework\Assert;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractFunctionalTestCase extends AbstractIntegrationTestCase
{
    use SalesChannelFunctionalTestBehaviour;
    use StorefrontRequestTrait;
    use StorefrontApiRequestTrait;

    protected function getSalesChannelId(): string
    {
        return TestDefaults::SALES_CHANNEL;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createSalesChannelContext(array $options = []): SalesChannelContext
    {
        $salesChannelId = $options['salesChannelId'] ?? $this->getSalesChannelId();

        return $this->getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), $salesChannelId, $options);
    }

    /**
     * Generate a URL from a route name and parameters.
     *
     * @param array<string, mixed> $params
     */
    protected function getUrl(string $routeName, array $params = []): string
    {
        $router = $this->getContainer()->get('router');
        Assert::assertInstanceOf(UrlGeneratorInterface::class, $router);

        return $router->generate($routeName, $params);
    }
}
