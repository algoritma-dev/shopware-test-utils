<?php

namespace Algoritma\ShopwareTestUtils\Traits;

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
     * @param array<string, mixed> $options
     */
    protected function storefrontApiBrowser(array $options = []): KernelBrowser
    {
        if (! $this->storefrontApiBrowserInstance instanceof KernelBrowser) {
            $this->storefrontApiBrowserInstance = $this->createCustomSalesChannelBrowser($options);
        }

        return $this->storefrontApiBrowserInstance;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function storefrontApiContext(array $options = []): SalesChannelContext
    {
        if (! $this->storefrontApiSalesChannelContext instanceof SalesChannelContext) {
            $this->storefrontApiSalesChannelContext = $this->createSalesChannelContext($options);
        }

        return $this->storefrontApiSalesChannelContext;
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
        \assert($contextFactory instanceof AbstractSalesChannelContextFactory);

        $salesChannelId = $this->storefrontApiContext()->getSalesChannelId();
        $contextAsArray = $contextPersister->load($contextToken, $salesChannelId);

        if ($contextAsArray === []) {
            return;
        }

        $this->storefrontApiSalesChannelContext = $contextFactory->create(
            $contextToken,
            $salesChannelId,
            $contextAsArray
        );
    }
}
