<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Algoritma\ShopwareTestUtils\Helper\B2B\CustomerFeatureToggleHelper;
use Algoritma\ShopwareTestUtils\Helper\CartHelper;
use Algoritma\ShopwareTestUtils\Helper\CheckoutHelper;
use Algoritma\ShopwareTestUtils\Helper\ConfigHelper;
use Algoritma\ShopwareTestUtils\Helper\CustomerHelper;
use Algoritma\ShopwareTestUtils\Helper\MailHelper;
use Algoritma\ShopwareTestUtils\Helper\MediaHelper;
use Algoritma\ShopwareTestUtils\Helper\MigrationDataTester;
use Algoritma\ShopwareTestUtils\Helper\OrderHelper;
use Algoritma\ShopwareTestUtils\Helper\ProductHelper;
use Algoritma\ShopwareTestUtils\Helper\SalesChannelHelper;
use Algoritma\ShopwareTestUtils\Helper\StateManager;
use Algoritma\ShopwareTestUtils\Helper\StorefrontRequestHelper;
use Algoritma\ShopwareTestUtils\Helper\TimeHelper;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;

/**
 * Provides easy access to helper classes in test cases.
 * Manages lazy initialization and caching of helper instances.
 */
trait HelperAccessorTrait
{
    use KernelTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private array $helpers = [];

    protected function cartHelper(): CartHelper
    {
        return $this->helpers[CartHelper::class] ??= new CartHelper(static::getContainer());
    }

    protected function checkoutRunner(): CheckoutHelper
    {
        return $this->helpers[CheckoutHelper::class] ??= new CheckoutHelper(static::getContainer());
    }

    protected function configHelper(): ConfigHelper
    {
        return $this->helpers[ConfigHelper::class] ??= new ConfigHelper(static::getContainer());
    }

    protected function customerHelper(): CustomerHelper
    {
        return $this->helpers[CustomerHelper::class] ??= new CustomerHelper();
    }

    protected function customerFeatureToggleHelper(): CustomerFeatureToggleHelper
    {
        return $this->helpers[CustomerFeatureToggleHelper::class] ??= new CustomerFeatureToggleHelper(static::getContainer());
    }

    protected function mailHelper(): MailHelper
    {
        return $this->helpers[MailHelper::class] ??= new MailHelper(static::getContainer());
    }

    protected function mediaHelper(): MediaHelper
    {
        return $this->helpers[MediaHelper::class] ??= new MediaHelper(static::getContainer());
    }

    protected function migrationDataTester(): MigrationDataTester
    {
        return $this->helpers[MigrationDataTester::class] ??= new MigrationDataTester(static::getContainer()->get(Connection::class));
    }

    protected function orderHelper(): OrderHelper
    {
        return $this->helpers[OrderHelper::class] ??= new OrderHelper(static::getContainer());
    }

    protected function productHelper(): ProductHelper
    {
        return $this->helpers[ProductHelper::class] ??= new ProductHelper();
    }

    protected function salesChannelHelper(): SalesChannelHelper
    {
        return $this->helpers[SalesChannelHelper::class] ??= new SalesChannelHelper(static::getContainer());
    }

    protected function stateManager(): StateManager
    {
        return $this->helpers[StateManager::class] ??= new StateManager(static::getContainer());
    }

    protected function storefrontRequestHelper(array $options = []): StorefrontRequestHelper
    {
        $browser = $this->createCustomSalesChannelBrowser($options);

        return $this->helpers[StorefrontRequestHelper::class] ??= new StorefrontRequestHelper($browser);
    }

    protected function timeHelper(): TimeHelper
    {
        return $this->helpers[TimeHelper::class] ??= new TimeHelper(static::getContainer());
    }
}
