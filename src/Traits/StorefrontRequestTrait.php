<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Algoritma\ShopwareTestUtils\Helper\StorefrontRequestHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait StorefrontRequestTrait
{
    private ?StorefrontRequestHelper $storefrontRequestHelper = null;

    /**
     * @param array<string, mixed> $options
     */
    abstract protected function createStorefrontHelper(array $options = []): StorefrontRequestHelper;

    /**
     * @param array<string, mixed> $options
     */
    protected function storefrontHelper(array $options = []): StorefrontRequestHelper
    {
        if (! $this->storefrontRequestHelper instanceof StorefrontRequestHelper) {
            $this->storefrontRequestHelper = $this->createStorefrontHelper($options);
        }

        return $this->storefrontRequestHelper;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function storefrontBrowser(array $options = []): KernelBrowser
    {
        return $this->storefrontHelper($options)->getBrowser();
    }

    protected function storefrontLogin(string $email, string $password = 'shopware'): void
    {
        $this->storefrontHelper()->login($email, $password);
    }

    protected function storefrontAddToCart(string $productId, int $quantity = 1): void
    {
        $this->storefrontHelper()->addToCart($productId, $quantity);
    }

    protected function storefrontVisitProductPage(string $productId): Crawler
    {
        return $this->storefrontHelper()->visitProductPage($productId);
    }

    protected function storefrontProceedToCheckout(): Crawler
    {
        return $this->storefrontHelper()->proceedToCheckout();
    }

    protected function storefrontSubmitOrder(): void
    {
        $this->storefrontHelper()->submitOrder();
    }

    protected function storefrontAssertResponseOk(Response $response, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseOk($response, $message);
    }

    protected function storefrontAssertResponseCreated(Response $response, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseCreated($response, $message);
    }

    protected function storefrontAssertResponseNotFound(Response $response, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseNotFound($response, $message);
    }

    protected function storefrontAssertResponseForbidden(Response $response, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseForbidden($response, $message);
    }

    protected function storefrontAssertResponseRedirects(Response $response, ?string $expectedUrl = null, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseRedirects($response, $expectedUrl, $message);
    }

    protected function storefrontAssertResponseBodyContains(Response $response, string $needle, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseBodyContains($response, $needle, $message);
    }

    protected function storefrontAssertResponseBodyNotContains(Response $response, string $needle, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseBodyNotContains($response, $needle, $message);
    }

    protected function storefrontAssertResponseIsJson(Response $response, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseIsJson($response, $message);
    }

    /**
     * @param array<mixed> $expectedData
     */
    protected function storefrontAssertResponseJsonEquals(Response $response, array $expectedData, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseJsonEquals($response, $expectedData, $message);
    }

    protected function storefrontAssertResponseJsonContains(Response $response, string $key, string $expectedValue, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseJsonContains($response, $key, $expectedValue, $message);
    }

    protected function storefrontAssertResponseHasHeader(Response $response, string $header, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseHasHeader($response, $header, $message);
    }

    protected function storefrontAssertResponseHeaderContains(Response $response, string $header, string $value, string $message = ''): void
    {
        $this->storefrontHelper()->assertResponseHeaderContains($response, $header, $value, $message);
    }

    protected function storefrontAssertRequestMethod(Request $request, string $method, string $message = ''): void
    {
        $this->storefrontHelper()->assertRequestMethod($request, $method, $message);
    }

    protected function storefrontAssertRequestHasHeader(Request $request, string $header, string $message = ''): void
    {
        $this->storefrontHelper()->assertRequestHasHeader($request, $header, $message);
    }

    protected function storefrontAssertRequestHasParameter(Request $request, string $key, string $message = ''): void
    {
        $this->storefrontHelper()->assertRequestHasParameter($request, $key, $message);
    }
}
