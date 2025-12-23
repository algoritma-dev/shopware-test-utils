<?php

namespace Algoritma\ShopwareTestUtils\Core;

use Algoritma\ShopwareTestUtils\Helper\StorefrontRequestHelper;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;

abstract class AbstractFunctionalTestCase extends AbstractIntegrationTestCase
{
    use SalesChannelFunctionalTestBehaviour;

    protected function createStorefrontHelper(array $options = []): StorefrontRequestHelper
    {
        // createCustomSalesChannelBrowser is provided by SalesChannelFunctionalTestBehaviour
        // It creates a browser with a specific SalesChannel context.

        // If no specific options, it creates a default one.
        $browser = $this->createCustomSalesChannelBrowser($options);

        return new StorefrontRequestHelper($browser);
    }
}
