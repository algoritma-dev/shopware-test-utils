<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Traits\StorefrontRequestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FunctionalAssertionsTest extends TestCase
{
    private object $helper;

    protected function setUp(): void
    {
        $browser = $this->createStub(KernelBrowser::class);
        $this->helper = new class($browser) {
            use StorefrontRequestTrait {
                storefrontAssertResponseOk as public;
                storefrontAssertResponseCreated as public;
                storefrontAssertResponseNotFound as public;
                storefrontAssertResponseForbidden as public;
                storefrontAssertResponseRedirects as public;
                storefrontAssertResponseBodyContains as public;
                storefrontAssertResponseBodyNotContains as public;
                storefrontAssertResponseIsJson as public;
                storefrontAssertResponseJsonEquals as public;
                storefrontAssertResponseJsonContains as public;
                storefrontAssertResponseHasHeader as public;
                storefrontAssertResponseHeaderContains as public;
                storefrontAssertRequestMethod as public;
                storefrontAssertRequestHasHeader as public;
                storefrontAssertRequestHasParameter as public;
            }

            public function __construct(private KernelBrowser $browser) {}

            protected function createCustomSalesChannelBrowser(array $options = []): KernelBrowser
            {
                return $this->browser;
            }
        };
    }

    public function testAssertResponseOk(): void
    {
        $response = new Response('', Response::HTTP_OK);
        $this->helper->storefrontAssertResponseOk($response, 'response ok');
        $this->assertTrue(true);
    }

    public function testAssertResponseCreated(): void
    {
        $response = new Response('', Response::HTTP_CREATED);
        $this->helper->storefrontAssertResponseCreated($response, 'response created');
        $this->assertTrue(true);
    }

    public function testAssertResponseNotFound(): void
    {
        $response = new Response('', Response::HTTP_NOT_FOUND);
        $this->helper->storefrontAssertResponseNotFound($response, 'response not found');
        $this->assertTrue(true);
    }

    public function testAssertResponseForbidden(): void
    {
        $response = new Response('', Response::HTTP_FORBIDDEN);
        $this->helper->storefrontAssertResponseForbidden($response, 'response forbidden');
        $this->assertTrue(true);
    }

    public function testAssertResponseRedirects(): void
    {
        $response = new Response('', Response::HTTP_FOUND);
        $response->headers->set('Location', '/target');
        $this->helper->storefrontAssertResponseRedirects($response, '/target', 'response redirects');
        $this->assertTrue(true);
    }

    public function testAssertResponseBodyContains(): void
    {
        $response = new Response('Hello World');
        $this->helper->storefrontAssertResponseBodyContains($response, 'World', 'response body contains');
        $this->assertTrue(true);
    }

    public function testAssertResponseBodyNotContains(): void
    {
        $response = new Response('Hello World');
        $this->helper->storefrontAssertResponseBodyNotContains($response, 'Foo', 'response body not contains');
        $this->assertTrue(true);
    }

    public function testAssertResponseIsJson(): void
    {
        $response = new Response('{"key": "value"}');
        $this->helper->storefrontAssertResponseIsJson($response, 'response is json');
        $this->assertTrue(true);
    }

    public function testAssertResponseJsonEquals(): void
    {
        $data = ['key' => 'value'];
        $response = new Response(json_encode($data));
        $this->helper->storefrontAssertResponseJsonEquals($response, $data, 'response json equals');
        $this->assertTrue(true);
    }

    public function testAssertResponseJsonContains(): void
    {
        $data = ['key' => 'value', 'foo' => 'bar'];
        $response = new Response(json_encode($data));
        $this->helper->storefrontAssertResponseJsonContains($response, 'key', 'value', 'response json contains');
        $this->assertTrue(true);
    }

    public function testAssertResponseHasHeader(): void
    {
        $response = new Response();
        $response->headers->set('X-Custom-Header', 'value');
        $this->helper->storefrontAssertResponseHasHeader($response, 'X-Custom-Header', 'response has header');
        $this->assertTrue(true);
    }

    public function testAssertResponseHeaderContains(): void
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $this->helper->storefrontAssertResponseHeaderContains($response, 'Content-Type', 'application/json', 'response header contains');
        $this->assertTrue(true);
    }

    public function testAssertRequestMethod(): void
    {
        $request = Request::create('/', 'POST');
        $this->helper->storefrontAssertRequestMethod($request, 'POST', 'request method');
        $this->assertTrue(true);
    }

    public function testAssertRequestHasHeader(): void
    {
        $request = Request::create('/');
        $request->headers->set('X-Custom-Header', 'value');
        $this->helper->storefrontAssertRequestHasHeader($request, 'X-Custom-Header', 'request has header');
        $this->assertTrue(true);
    }

    public function testAssertRequestHasParameter(): void
    {
        $request = Request::create('/', 'GET', ['param' => 'value']);
        $this->helper->storefrontAssertRequestHasParameter($request, 'param', 'request has parameter');
        $this->assertTrue(true);
    }
}
