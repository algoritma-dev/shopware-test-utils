<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\CustomerFeatureToggleHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

trait B2BCustomerFeatureTrait
{
    use KernelTestBehaviour;

    private ?CustomerFeatureToggleHelper $b2bCustomerFeatureToggleHelperInstance = null;

    protected function getB2bCustomerFeatureToggleHelper(): CustomerFeatureToggleHelper
    {
        if (! $this->b2bCustomerFeatureToggleHelperInstance instanceof CustomerFeatureToggleHelper) {
            $this->b2bCustomerFeatureToggleHelperInstance = new CustomerFeatureToggleHelper(static::getContainer());
        }

        return $this->b2bCustomerFeatureToggleHelperInstance;
    }

    protected function b2bCustomerFeatureEnable(string $customerId, string $featureCode, ?Context $context = null): void
    {
        $this->getB2bCustomerFeatureToggleHelper()->enableFeature($customerId, $featureCode, $context);
    }

    protected function b2bCustomerFeatureDisable(string $customerId, string $featureCode, ?Context $context = null): void
    {
        $this->getB2bCustomerFeatureToggleHelper()->disableFeature($customerId, $featureCode, $context);
    }

    protected function b2bCustomerFeatureIsEnabled(string $customerId, string $featureCode, ?Context $context = null): bool
    {
        return $this->getB2bCustomerFeatureToggleHelper()->isFeatureEnabled($customerId, $featureCode, $context);
    }

    protected function b2bCustomerFeatureEnableAll(string $customerId, ?Context $context = null): void
    {
        $this->getB2bCustomerFeatureToggleHelper()->enableAllFeatures($customerId, $context);
    }

    protected function b2bCustomerFeatureDisableAll(string $customerId, ?Context $context = null): void
    {
        $this->getB2bCustomerFeatureToggleHelper()->disableAllFeatures($customerId, $context);
    }
}
