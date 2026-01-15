<?php

namespace Algoritma\ShopwareTestUtils\Core;

use Algoritma\ShopwareTestUtils\Helper\StorefrontRequestHelper;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractFunctionalTestCase extends AbstractIntegrationTestCase
{
    use SalesChannelFunctionalTestBehaviour;

    /**
     * @param array<string, mixed> $options
     */
    protected function createStorefrontHelper(array $options = []): StorefrontRequestHelper
    {
        // createCustomSalesChannelBrowser is provided by SalesChannelFunctionalTestBehaviour
        // It creates a browser with a specific SalesChannel context.
        $browser = $this->createCustomSalesChannelBrowser($options);

        $salesChannelContext = $this->getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), $options['id'], $options);

        return new StorefrontRequestHelper($browser, $salesChannelContext);
    }

    /**
     * Generate a URL from a route name and parameters.
     *
     * @param array<string, mixed> $params
     */
    protected function getUrl(string $routeName, array $params = []): string
    {
        $router = $this->getContainer()->get('router');
        \assert($router instanceof UrlGeneratorInterface);

        return $router->generate($routeName, $params);
    }
}
