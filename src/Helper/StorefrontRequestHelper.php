<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StorefrontRequestHelper
{
    public function __construct(
        private readonly KernelBrowser $browser
    ) {}

    /**
     * Login to the storefront for using throught controller request, with given credentials.
     *
     * Usefull for testing storefront routes.
     */
    public function login(string $email, string $password = 'shopware'): void
    {
        $this->browser
            ->request(
                'POST',
                '/account/login',
                [
                    'email' => $email,
                    'password' => $password,
                ]
            );
    }

    public function addToCart(string $productId, int $quantity = 1): void
    {
        $this->browser->request(
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

        // Expect redirect or success
        $this->assertSuccessOrRedirect();
    }

    public function visitProductPage(string $productId): Crawler
    {
        // Note: URL structure depends on SEO settings, but usually accessible via ID or technical route if SEO is not generated in tests.
        // Often in tests we might need the SEO url or use the technical controller.
        // For simplicity, let's assume we can search or use a known route, but standard Shopware doesn't expose /product/{id} easily without SEO.
        // A robust way is to use the 'detail' route with the productId.

        // Trying technical route often used in tests or fallback
        // Actually, the best way in functional tests is often to rely on the navigation or search.
        // But let's try to use the SEO URL if we had it, or just assume standard routing.
        // Let's use the detail controller route if possible, or just skip this helper if too complex for generic setup.

        // Alternative: Add via ID is done. Viewing is less critical for "actions" but good for assertions.
        return $this->browser->request('GET', '/detail/' . $productId);
    }

    public function proceedToCheckout(): Crawler
    {
        return $this->browser->request('GET', '/checkout/confirm');
    }

    public function submitOrder(): void
    {
        // Usually requires CSRF token handling if enabled, but in test env often disabled or handled by browser.
        // We need to find the form in the checkout confirm page and submit it.

        $crawler = $this->browser->request('GET', '/checkout/confirm');
        $form = $crawler->filter('#confirmOrderForm')->form();

        $this->browser->submit($form);
        $this->assertSuccessOrRedirect();
    }

    public function getBrowser(): KernelBrowser
    {
        return $this->browser;
    }

    // --- Response Status Assertions ---

    /**
     * Assert that the response status code is 200 OK.
     */
    public function assertResponseOk(Response $response, string $message = ''): void
    {
        \assert(
            $response->getStatusCode() === Response::HTTP_OK,
            $message ?: \sprintf('Expected response status code 200, but got %d', $response->getStatusCode())
        );
    }

    /**
     * Assert that the response status code is 201 Created.
     */
    public function assertResponseCreated(Response $response, string $message = ''): void
    {
        \assert(
            $response->getStatusCode() === Response::HTTP_CREATED,
            $message ?: \sprintf('Expected response status code 201, but got %d', $response->getStatusCode())
        );
    }

    /**
     * Assert that the response status code is 404 Not Found.
     */
    public function assertResponseNotFound(Response $response, string $message = ''): void
    {
        \assert(
            $response->getStatusCode() === Response::HTTP_NOT_FOUND,
            $message ?: \sprintf('Expected response status code 404, but got %d', $response->getStatusCode())
        );
    }

    /**
     * Assert that the response status code is 403 Forbidden.
     */
    public function assertResponseForbidden(Response $response, string $message = ''): void
    {
        \assert(
            $response->getStatusCode() === Response::HTTP_FORBIDDEN,
            $message ?: \sprintf('Expected response status code 403, but got %d', $response->getStatusCode())
        );
    }

    /**
     * Assert that the response status code is a redirect (3xx).
     */
    public function assertResponseRedirects(Response $response, ?string $expectedUrl = null, string $message = ''): void
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

    // --- Response Body Assertions ---

    /**
     * Assert that the response body contains a specific string.
     */
    public function assertResponseBodyContains(Response $response, string $needle, string $message = ''): void
    {
        $content = (string) $response->getContent();
        \assert(
            str_contains($content, $needle),
            $message ?: \sprintf('Expected response body to contain "%s"', $needle)
        );
    }

    /**
     * Assert that the response body does not contain a specific string.
     */
    public function assertResponseBodyNotContains(Response $response, string $needle, string $message = ''): void
    {
        $content = (string) $response->getContent();
        \assert(
            ! str_contains($content, $needle),
            $message ?: \sprintf('Expected response body not to contain "%s"', $needle)
        );
    }

    /**
     * Assert that the response is a valid JSON response.
     */
    public function assertResponseIsJson(Response $response, string $message = ''): void
    {
        $content = (string) $response->getContent();
        \json_decode($content);
        \assert(
            \json_last_error() === JSON_ERROR_NONE,
            $message ?: 'Expected response to be valid JSON'
        );
    }

    /**
     * Assert that the JSON response matches a specific array structure/data.
     *
     * @param array<mixed> $expectedData
     */
    public function assertResponseJsonEquals(Response $response, array $expectedData, string $message = ''): void
    {
        $this->assertResponseIsJson($response, $message);
        $content = (string) $response->getContent();
        $actualData = \json_decode($content, true);

        \assert(
            $actualData === $expectedData,
            $message ?: 'Expected JSON response to match provided data'
        );
    }

    /**
     * Assert that the JSON response contains a specific key-value pair.
     */
    public function assertResponseJsonContains(Response $response, string $key, $expectedValue, string $message = ''): void
    {
        $this->assertResponseIsJson($response, $message);
        $content = (string) $response->getContent();
        $actualData = \json_decode($content, true);

        \assert(\array_key_exists($key, $actualData), $message ?: \sprintf('Expected JSON response to contain key "%s"', $key));
        \assert($actualData[$key] === $expectedValue, $message ?: \sprintf('Expected JSON key "%s" to be "%s"', $key, \print_r($expectedValue, true)));
    }

    // --- Response Header Assertions ---

    /**
     * Assert that the response has a specific header.
     */
    public function assertResponseHasHeader(Response $response, string $header, string $message = ''): void
    {
        \assert(
            $response->headers->has($header),
            $message ?: \sprintf('Expected response to have header "%s"', $header)
        );
    }

    /**
     * Assert that the response header contains a specific value.
     */
    public function assertResponseHeaderContains(Response $response, string $header, string $value, string $message = ''): void
    {
        $this->assertResponseHasHeader($response, $header, $message);
        \assert(
            str_contains((string) $response->headers->get($header), $value),
            $message ?: \sprintf('Expected response header "%s" to contain "%s"', $header, $value)
        );
    }

    // --- Request Assertions ---

    /**
     * Assert that the request method matches.
     */
    public function assertRequestMethod(Request $request, string $method, string $message = ''): void
    {
        \assert(
            $request->getMethod() === \strtoupper($method),
            $message ?: \sprintf('Expected request method "%s", but got "%s"', \strtoupper($method), $request->getMethod())
        );
    }

    /**
     * Assert that the request has a specific header.
     */
    public function assertRequestHasHeader(Request $request, string $header, string $message = ''): void
    {
        \assert(
            $request->headers->has($header),
            $message ?: \sprintf('Expected request to have header "%s"', $header)
        );
    }

    /**
     * Assert that the request contains a specific parameter.
     */
    public function assertRequestHasParameter(Request $request, string $key, string $message = ''): void
    {
        \assert(
            $request->query->has($key) || $request->request->has($key),
            $message ?: \sprintf('Expected request to have parameter "%s"', $key)
        );
    }

    private function assertSuccessOrRedirect(): void
    {
        $statusCode = $this->browser->getResponse()->getStatusCode();
        if ($statusCode >= 400) {
            throw new \RuntimeException('Request failed with status code: ' . $statusCode);
        }
    }
}
