<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assertions for functional tests (HTTP requests/responses).
 */
trait FunctionalAssertions
{
    // ... Response Status Assertions ...

    /**
     * Assert that the response status code is 200 OK.
     */
    protected function assertResponseOk(Response $response, string $message = ''): void
    {
        static::assertEquals(
            Response::HTTP_OK,
            $response->getStatusCode(),
            $message ?: sprintf('Expected response status code 200, but got %d', $response->getStatusCode())
        );
    }

    /**
     * Assert that the response status code is 201 Created.
     */
    protected function assertResponseCreated(Response $response, string $message = ''): void
    {
        static::assertEquals(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            $message ?: sprintf('Expected response status code 201, but got %d', $response->getStatusCode())
        );
    }

    /**
     * Assert that the response status code is 404 Not Found.
     */
    protected function assertResponseNotFound(Response $response, string $message = ''): void
    {
        static::assertEquals(
            Response::HTTP_NOT_FOUND,
            $response->getStatusCode(),
            $message ?: sprintf('Expected response status code 404, but got %d', $response->getStatusCode())
        );
    }

    /**
     * Assert that the response status code is 403 Forbidden.
     */
    protected function assertResponseForbidden(Response $response, string $message = ''): void
    {
        static::assertEquals(
            Response::HTTP_FORBIDDEN,
            $response->getStatusCode(),
            $message ?: sprintf('Expected response status code 403, but got %d', $response->getStatusCode())
        );
    }

    /**
     * Assert that the response status code is a redirect (3xx).
     */
    protected function assertResponseRedirects(Response $response, ?string $expectedUrl = null, string $message = ''): void
    {
        static::assertTrue(
            $response->isRedirection(),
            $message ?: sprintf('Expected response to be a redirect, but got status code %d', $response->getStatusCode())
        );

        if ($expectedUrl !== null) {
            static::assertEquals(
                $expectedUrl,
                $response->headers->get('Location'),
                $message ?: sprintf('Expected redirect to "%s", but got "%s"', $expectedUrl, $response->headers->get('Location'))
            );
        }
    }

    // ... Response Body Assertions ...

    /**
     * Assert that the response body contains a specific string.
     */
    protected function assertResponseBodyContains(Response $response, string $needle, string $message = ''): void
    {
        $content = (string) $response->getContent();
        static::assertStringContainsString(
            $needle,
            $content,
            $message ?: sprintf('Expected response body to contain "%s"', $needle)
        );
    }

    /**
     * Assert that the response body does not contain a specific string.
     */
    protected function assertResponseBodyNotContains(Response $response, string $needle, string $message = ''): void
    {
        $content = (string) $response->getContent();
        static::assertStringNotContainsString(
            $needle,
            $content,
            $message ?: sprintf('Expected response body not to contain "%s"', $needle)
        );
    }

    /**
     * Assert that the response is a valid JSON response.
     */
    protected function assertResponseIsJson(Response $response, string $message = ''): void
    {
        $content = (string) $response->getContent();
        static::assertJson(
            $content,
            $message ?: 'Expected response to be valid JSON'
        );
    }

    /**
     * Assert that the JSON response matches a specific array structure/data.
     *
     * @param array<mixed> $expectedData
     */
    protected function assertResponseJsonEquals(Response $response, array $expectedData, string $message = ''): void
    {
        $this->assertResponseIsJson($response, $message);
        $content = (string) $response->getContent();
        $actualData = json_decode($content, true);

        static::assertEquals(
            $expectedData,
            $actualData,
            $message ?: 'Expected JSON response to match provided data'
        );
    }

    /**
     * Assert that the JSON response contains a specific key-value pair.
     */
    protected function assertResponseJsonContains(Response $response, string $key, $expectedValue, string $message = ''): void
    {
        $this->assertResponseIsJson($response, $message);
        $content = (string) $response->getContent();
        $actualData = json_decode($content, true);

        static::assertArrayHasKey($key, $actualData, $message ?: sprintf('Expected JSON response to contain key "%s"', $key));
        static::assertEquals($expectedValue, $actualData[$key], $message ?: sprintf('Expected JSON key "%s" to be "%s"', $key, print_r($expectedValue, true)));
    }

    // ... Response Header Assertions ...

    /**
     * Assert that the response has a specific header.
     */
    protected function assertResponseHasHeader(Response $response, string $header, string $message = ''): void
    {
        static::assertTrue(
            $response->headers->has($header),
            $message ?: sprintf('Expected response to have header "%s"', $header)
        );
    }

    /**
     * Assert that the response header contains a specific value.
     */
    protected function assertResponseHeaderContains(Response $response, string $header, string $value, string $message = ''): void
    {
        $this->assertResponseHasHeader($response, $header, $message);
        static::assertStringContainsString(
            $value,
            (string) $response->headers->get($header),
            $message ?: sprintf('Expected response header "%s" to contain "%s"', $header, $value)
        );
    }

    // ... Request Assertions ...

    /**
     * Assert that the request method matches.
     */
    protected function assertRequestMethod(Request $request, string $method, string $message = ''): void
    {
        static::assertEquals(
            strtoupper($method),
            $request->getMethod(),
            $message ?: sprintf('Expected request method "%s", but got "%s"', strtoupper($method), $request->getMethod())
        );
    }

    /**
     * Assert that the request has a specific header.
     */
    protected function assertRequestHasHeader(Request $request, string $header, string $message = ''): void
    {
        static::assertTrue(
            $request->headers->has($header),
            $message ?: sprintf('Expected request to have header "%s"', $header)
        );
    }

    /**
     * Assert that the request contains a specific parameter.
     */
    protected function assertRequestHasParameter(Request $request, string $key, string $message = ''): void
    {
        static::assertTrue(
            $request->query->has($key) || $request->request->has($key),
            $message ?: sprintf('Expected request to have parameter "%s"', $key)
        );
    }
}
