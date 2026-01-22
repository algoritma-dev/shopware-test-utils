<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class StorefrontApiRequestHelper
{
    public function __construct(
        private readonly KernelBrowser $browser,
        private SalesChannelContext $salesChannelContext
    ) {}

    /**
     * Login to the store-api with given credentials.
     *
     * Usefull for testing store-api routes.
     */
    public function login(string $email, string $password = 'shopware'): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => $password,
                ]
            );

        $response = $this->browser->getResponse();

        // After login successfully, the context token will be set in the header
        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        if (empty($contextToken)) {
            throw new \RuntimeException('Cannot login with the given credential account');
        }

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);

        // Reload the SalesChannelContext with the new context token
        $this->reloadSalesChannelContext($contextToken);
    }

    private function reloadSalesChannelContext(string $contextToken): void
    {
        $container = $this->browser->getContainer();
        $contextFactory = $container->get(SalesChannelContextFactory::class);
        \assert($contextFactory instanceof AbstractSalesChannelContextFactory);

        $this->salesChannelContext = $contextFactory->create(
            $contextToken,
            $this->salesChannelContext->getSalesChannelId()
        );
    }
}
