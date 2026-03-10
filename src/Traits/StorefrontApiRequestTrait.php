<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait StorefrontApiRequestTrait
{
    private ?KernelBrowser $storefrontApiBrowserInstance = null;

    private ?SalesChannelContext $storefrontApiSalesChannelContext = null;

    /**
     * @param array<string, mixed> $options
     */
    abstract protected function createCustomSalesChannelBrowser(array $options = []): KernelBrowser;

    /**
     * @param array<string, mixed> $options
     */
    abstract protected function createSalesChannelContext(array $options = []): SalesChannelContext;

    /**
     * Get the storefront API browser instance.
     *
     * Note: This method returns a cached browser instance created with default options.
     * To create a browser with custom options, use createStorefrontApiBrowser().
     * To reset the browser instance, use resetStorefrontApiBrowser().
     */
    protected function storefrontApiBrowser(): KernelBrowser
    {
        if (! $this->storefrontApiBrowserInstance instanceof KernelBrowser) {
            $this->storefrontApiBrowserInstance = $this->createCustomSalesChannelBrowser([]);
        }

        return $this->storefrontApiBrowserInstance;
    }

    /**
     * Create a new storefront API browser instance with custom options.
     * This will overwrite the existing cached instance.
     *
     * @param array<string, mixed> $options
     */
    protected function createStorefrontApiBrowser(array $options = []): KernelBrowser
    {
        return $this->storefrontApiBrowserInstance = $this->createCustomSalesChannelBrowser($options);
    }

    /**
     * Reset the storefront API browser instance.
     * Use this to force the creation of a new browser with different options.
     */
    protected function resetStorefrontApiBrowser(): void
    {
        $this->storefrontApiBrowserInstance = null;
    }

    /**
     * Get the storefront API context instance.
     *
     * Note: This method returns a cached context instance created with default options.
     * To create a context with custom options, use createStorefrontApiContext().
     * To reset the context instance, use resetStorefrontApiContext().
     */
    protected function storefrontApiContext(): SalesChannelContext
    {
        if (! $this->storefrontApiSalesChannelContext instanceof SalesChannelContext) {
            $this->storefrontApiSalesChannelContext = $this->createSalesChannelContext([]);
        }

        return $this->storefrontApiSalesChannelContext;
    }

    /**
     * Create a new storefront API context instance with custom options.
     * This will overwrite the existing cached instance.
     *
     * @param array<string, mixed> $options
     */
    protected function createStorefrontApiContext(array $options = []): SalesChannelContext
    {
        return $this->storefrontApiSalesChannelContext = $this->createSalesChannelContext($options);
    }

    /**
     * Reset the storefront API context instance.
     * Use this to force the creation of a new context with different options.
     */
    protected function resetStorefrontApiContext(): void
    {
        $this->storefrontApiSalesChannelContext = null;
    }

    protected function storefrontApiLogin(string $email, string $password = 'shopware'): void
    {
        $this->storefrontApiBrowser()
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => $password,
                ]
            );

        $response = $this->storefrontApiBrowser()->getResponse();

        // After login successfully, the context token will be set in the header
        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        if (empty($contextToken)) {
            throw new \RuntimeException('Cannot login with the given credential account');
        }

        $this->storefrontApiBrowser()->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);

        // Reload the SalesChannelContext with the new context token
        $this->reloadStorefrontApiSalesChannelContext($contextToken);
    }

    protected function storefrontApiLogout(): void
    {
        $this->storefrontApiBrowser()
            ->request(
                'POST',
                '/store-api/account/logout'
            );

        $this->storefrontApiBrowser()->setServerParameter('HTTP_SW_CONTEXT_TOKEN', '');

        $this->reloadStorefrontApiSalesChannelContext('');
    }

    private function reloadStorefrontApiSalesChannelContext(string $contextToken): void
    {
        $container = $this->storefrontApiBrowser()->getContainer();
        $contextPersister = $container->get(SalesChannelContextPersister::class);
        $contextFactory = $container->get(SalesChannelContextFactory::class);
        Assert::assertInstanceOf(AbstractSalesChannelContextFactory::class, $contextFactory);

        $salesChannelId = $this->storefrontApiContext()->getSalesChannelId();
        $contextAsArray = $contextPersister->load($contextToken, $salesChannelId);

        if ($contextAsArray === []) {
            $this->storefrontApiSalesChannelContext = null;

            return;
        }

        $this->storefrontApiSalesChannelContext = $contextFactory->create(
            $contextToken,
            $salesChannelId,
            $contextAsArray
        );
    }
}
