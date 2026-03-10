<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait StorefrontRequestTrait
{
    private ?KernelBrowser $storefrontBrowserInstance = null;

    /**
     * @param array<string, mixed> $options
     */
    abstract protected function createCustomSalesChannelBrowser(array $options = []): KernelBrowser;

    /**
     * @param array<string, mixed> $options
     */
    protected function storefrontBrowser(array $options = []): KernelBrowser
    {
        if (! $this->storefrontBrowserInstance instanceof KernelBrowser) {
            $this->storefrontBrowserInstance = $this->createCustomSalesChannelBrowser($options);
        }

        return $this->storefrontBrowserInstance;
    }

    protected function storefrontLogin(string $email, string $password = 'shopware'): void
    {
        $this->storefrontBrowser()->request(
            'POST',
            '/account/login',
            [
                'email' => $email,
                'password' => $password,
            ]
        );
    }

    protected function storefrontAddToCart(string $productId, int $quantity = 1): void
    {
        $this->storefrontBrowser()->request(
            'POST',
            '/checkout/line-item/add',
            [
                'lineItems' => [
                    $productId => [
                        'id' => $productId,
                        'referencedId' => $productId,
                        'type' => 'product',
                        'quantity' => $quantity,
                        'stackable' => true,
                        'removable' => true,
                    ],
                ],
            ]
        );

        $this->assertStorefrontSuccessOrRedirect();
    }

    protected function storefrontVisitProductPage(string $productId): Crawler
    {
        return $this->storefrontBrowser()->request('GET', '/detail/' . $productId);
    }

    protected function storefrontProceedToCheckout(): Crawler
    {
        return $this->storefrontBrowser()->request('GET', '/checkout/confirm');
    }

    protected function storefrontSubmitOrder(): void
    {
        $crawler = $this->storefrontBrowser()->request('GET', '/checkout/confirm');
        $form = $crawler->filter('#confirmOrderForm')->form();

        $this->storefrontBrowser()->submit($form);
        $this->assertStorefrontSuccessOrRedirect();
    }

    protected function storefrontAssertResponseOk(Response $response, string $message = ''): void
    {
        \assert(
            $response->getStatusCode() === Response::HTTP_OK,
            $message ?: \sprintf('Expected response status code 200, but got %d', $response->getStatusCode())
        );
    }

    protected function storefrontAssertResponseCreated(Response $response, string $message = ''): void
    {
        \assert(
            $response->getStatusCode() === Response::HTTP_CREATED,
            $message ?: \sprintf('Expected response status code 201, but got %d', $response->getStatusCode())
        );
    }

    protected function storefrontAssertResponseNotFound(Response $response, string $message = ''): void
    {
        \assert(
            $response->getStatusCode() === Response::HTTP_NOT_FOUND,
            $message ?: \sprintf('Expected response status code 404, but got %d', $response->getStatusCode())
        );
    }

    protected function storefrontAssertResponseForbidden(Response $response, string $message = ''): void
    {
        \assert(
            $response->getStatusCode() === Response::HTTP_FORBIDDEN,
            $message ?: \sprintf('Expected response status code 403, but got %d', $response->getStatusCode())
        );
    }

    protected function storefrontAssertResponseRedirects(Response $response, ?string $expectedUrl = null, string $message = ''): void
    {
        \assert(
            $response->isRedirection(),
            $message ?: \sprintf('Expected response to be a redirect, but got status code %d', $response->getStatusCode())
        );

        if ($expectedUrl !== null) {
            \assert(
                $response->headers->get('Location') === $expectedUrl,
                $message ?: \sprintf('Expected redirect to "%s", but got "%s"', $expectedUrl, $response->headers->get('Location'))
            );
        }
    }

    protected function storefrontAssertResponseBodyContains(Response $response, string $needle, string $message = ''): void
    {
        $content = (string) $response->getContent();
        \assert(
            str_contains($content, $needle),
            $message ?: \sprintf('Expected response body to contain "%s"', $needle)
        );
    }

    protected function storefrontAssertResponseBodyNotContains(Response $response, string $needle, string $message = ''): void
    {
        $content = (string) $response->getContent();
        \assert(
            ! str_contains($content, $needle),
            $message ?: \sprintf('Expected response body not to contain "%s"', $needle)
        );
    }

    protected function storefrontAssertResponseIsJson(Response $response, string $message = ''): void
    {
        $content = (string) $response->getContent();
        \json_decode($content);
        \assert(
            \json_last_error() === JSON_ERROR_NONE,
            $message ?: 'Expected response to be valid JSON'
        );
    }

    /**
     * @param array<mixed> $expectedData
     */
    protected function storefrontAssertResponseJsonEquals(Response $response, array $expectedData, string $message = ''): void
    {
        $this->storefrontAssertResponseIsJson($response, $message);
        $content = (string) $response->getContent();
        $actualData = \json_decode($content, true);

        \assert(
            $actualData === $expectedData,
            $message ?: 'Expected JSON response to match provided data'
        );
    }

    protected function storefrontAssertResponseJsonContains(Response $response, string $key, string $expectedValue, string $message = ''): void
    {
        $this->storefrontAssertResponseIsJson($response, $message);
        $content = (string) $response->getContent();
        $actualData = \json_decode($content, true);

        \assert(\array_key_exists($key, $actualData), $message ?: \sprintf('Expected JSON response to contain key "%s"', $key));
        \assert($actualData[$key] === $expectedValue, $message ?: \sprintf('Expected JSON key "%s" to be "%s"', $key, \print_r($expectedValue, true)));
    }

    protected function storefrontAssertResponseHasHeader(Response $response, string $header, string $message = ''): void
    {
        \assert(
            $response->headers->has($header),
            $message ?: \sprintf('Expected response to have header "%s"', $header)
        );
    }

    protected function storefrontAssertResponseHeaderContains(Response $response, string $header, string $value, string $message = ''): void
    {
        $this->storefrontAssertResponseHasHeader($response, $header, $message);
        \assert(
            str_contains((string) $response->headers->get($header), $value),
            $message ?: \sprintf('Expected response header "%s" to contain "%s"', $header, $value)
        );
    }

    protected function storefrontAssertRequestMethod(Request $request, string $method, string $message = ''): void
    {
        \assert(
            $request->getMethod() === \strtoupper($method),
            $message ?: \sprintf('Expected request method "%s", but got "%s"', \strtoupper($method), $request->getMethod())
        );
    }

    protected function storefrontAssertRequestHasHeader(Request $request, string $header, string $message = ''): void
    {
        \assert(
            $request->headers->has($header),
            $message ?: \sprintf('Expected request to have header "%s"', $header)
        );
    }

    protected function storefrontAssertRequestHasParameter(Request $request, string $key, string $message = ''): void
    {
        \assert(
            $request->query->has($key) || $request->request->has($key),
            $message ?: \sprintf('Expected request to have parameter "%s"', $key)
        );
    }

    private function assertStorefrontSuccessOrRedirect(): void
    {
        $statusCode = $this->storefrontBrowser()->getResponse()->getStatusCode();
        if ($statusCode >= 400) {
            throw new \RuntimeException('Request failed with status code: ' . $statusCode);
        }
    }
}
