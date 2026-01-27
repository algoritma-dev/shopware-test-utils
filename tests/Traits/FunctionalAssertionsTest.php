<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Helper\StorefrontRequestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FunctionalAssertionsTest extends TestCase
{
    private StorefrontRequestHelper $helper;

    protected function setUp(): void
    {
        $browser = $this->createStub(KernelBrowser::class);
        $this->helper = new StorefrontRequestHelper($browser);
    }

    public function testAssertResponseOk(): void
    {
        $response = new Response('', Response::HTTP_OK);
        $this->helper->assertResponseOk($response, 'response ok');
        $this->assertTrue(true);
    }

    public function testAssertResponseCreated(): void
    {
        $response = new Response('', Response::HTTP_CREATED);
        $this->helper->assertResponseCreated($response, 'response created');
        $this->assertTrue(true);
    }

    public function testAssertResponseNotFound(): void
    {
        $response = new Response('', Response::HTTP_NOT_FOUND);
        $this->helper->assertResponseNotFound($response, 'response not found');
        $this->assertTrue(true);
    }

    public function testAssertResponseForbidden(): void
    {
        $response = new Response('', Response::HTTP_FORBIDDEN);
        $this->helper->assertResponseForbidden($response, 'response forbidden');
        $this->assertTrue(true);
    }

    public function testAssertResponseRedirects(): void
    {
        $response = new Response('', Response::HTTP_FOUND);
        $response->headers->set('Location', '/target');
        $this->helper->assertResponseRedirects($response, '/target', 'response redirects');
        $this->assertTrue(true);
    }

    public function testAssertResponseBodyContains(): void
    {
        $response = new Response('Hello World');
        $this->helper->assertResponseBodyContains($response, 'World', 'response body contains');
        $this->assertTrue(true);
    }

    public function testAssertResponseBodyNotContains(): void
    {
        $response = new Response('Hello World');
        $this->helper->assertResponseBodyNotContains($response, 'Foo', 'response body not contains');
        $this->assertTrue(true);
    }

    public function testAssertResponseIsJson(): void
    {
        $response = new Response('{"key": "value"}');
        $this->helper->assertResponseIsJson($response, 'response is json');
        $this->assertTrue(true);
    }

    public function testAssertResponseJsonEquals(): void
    {
        $data = ['key' => 'value'];
        $response = new Response(json_encode($data));
        $this->helper->assertResponseJsonEquals($response, $data, 'response json equals');
        $this->assertTrue(true);
    }

    public function testAssertResponseJsonContains(): void
    {
        $data = ['key' => 'value', 'foo' => 'bar'];
        $response = new Response(json_encode($data));
        $this->helper->assertResponseJsonContains($response, 'key', 'value', 'response json contains');
        $this->assertTrue(true);
    }

    public function testAssertResponseHasHeader(): void
    {
        $response = new Response();
        $response->headers->set('X-Custom-Header', 'value');
        $this->helper->assertResponseHasHeader($response, 'X-Custom-Header', 'response has header');
        $this->assertTrue(true);
    }

    public function testAssertResponseHeaderContains(): void
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $this->helper->assertResponseHeaderContains($response, 'Content-Type', 'application/json', 'response header contains');
        $this->assertTrue(true);
    }

    public function testAssertRequestMethod(): void
    {
        $request = Request::create('/', 'POST');
        $this->helper->assertRequestMethod($request, 'POST', 'request method');
        $this->assertTrue(true);
    }

    public function testAssertRequestHasHeader(): void
    {
        $request = Request::create('/');
        $request->headers->set('X-Custom-Header', 'value');
        $this->helper->assertRequestHasHeader($request, 'X-Custom-Header', 'request has header');
        $this->assertTrue(true);
    }

    public function testAssertRequestHasParameter(): void
    {
        $request = Request::create('/', 'GET', ['param' => 'value']);
        $this->helper->assertRequestHasParameter($request, 'param', 'request has parameter');
        $this->assertTrue(true);
    }
}
