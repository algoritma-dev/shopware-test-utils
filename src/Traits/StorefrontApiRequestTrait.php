<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Algoritma\ShopwareTestUtils\Helper\StorefrontApiRequestHelper;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait StorefrontApiRequestTrait
{
    private ?StorefrontApiRequestHelper $storefrontApiRequestHelper = null;

    /**
     * @param array<string, mixed> $options
     */
    abstract protected function createStorefrontApiHelper(array $options = []): StorefrontApiRequestHelper;

    /**
     * @param array<string, mixed> $options
     */
    protected function storefrontApiHelper(array $options = []): StorefrontApiRequestHelper
    {
        if (! $this->storefrontApiRequestHelper instanceof StorefrontApiRequestHelper) {
            $this->storefrontApiRequestHelper = $this->createStorefrontApiHelper($options);
        }

        return $this->storefrontApiRequestHelper;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function storefrontApiBrowser(array $options = []): KernelBrowser
    {
        return $this->storefrontApiHelper($options)->getBrowser();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function storefrontApiContext(array $options = []): SalesChannelContext
    {
        return $this->storefrontApiHelper($options)->getSalesChannelContext();
    }

    protected function storefrontApiLogin(string $email, string $password = 'shopware'): void
    {
        $this->storefrontApiHelper()->login($email, $password);
    }

    protected function storefrontApiLogout(): void
    {
        $this->storefrontApiHelper()->logout();
    }
}
