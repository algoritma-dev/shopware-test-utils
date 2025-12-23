<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Traits\FunctionalAssertions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FunctionalAssertionsTest extends TestCase
{
    use FunctionalAssertions;

    public function testAssertResponseOk(): void
    {
        $response = new Response('', Response::HTTP_OK);
        $this->assertResponseOk($response);
    }

    public function testAssertResponseCreated(): void
    {
        $response = new Response('', Response::HTTP_CREATED);
        $this->assertResponseCreated($response);
    }

    public function testAssertResponseNotFound(): void
    {
        $response = new Response('', Response::HTTP_NOT_FOUND);
        $this->assertResponseNotFound($response);
    }

    public function testAssertResponseForbidden(): void
    {
        $response = new Response('', Response::HTTP_FORBIDDEN);
        $this->assertResponseForbidden($response);
    }

    public function testAssertResponseRedirects(): void
    {
        $response = new Response('', Response::HTTP_FOUND);
        $response->headers->set('Location', '/target');
        $this->assertResponseRedirects($response, '/target');
    }

    public function testAssertResponseBodyContains(): void
    {
        $response = new Response('Hello World');
        $this->assertResponseBodyContains($response, 'World');
    }

    public function testAssertResponseBodyNotContains(): void
    {
        $response = new Response('Hello World');
        $this->assertResponseBodyNotContains($response, 'Foo');
    }

    public function testAssertResponseIsJson(): void
    {
        $response = new Response('{"key": "value"}');
        $this->assertResponseIsJson($response);
    }

    public function testAssertResponseJsonEquals(): void
    {
        $data = ['key' => 'value'];
        $response = new Response(json_encode($data));
        $this->assertResponseJsonEquals($response, $data);
    }

    public function testAssertResponseJsonContains(): void
    {
        $data = ['key' => 'value', 'foo' => 'bar'];
        $response = new Response(json_encode($data));
        $this->assertResponseJsonContains($response, 'key', 'value');
    }

    public function testAssertResponseHasHeader(): void
    {
        $response = new Response();
        $response->headers->set('X-Custom-Header', 'value');
        $this->assertResponseHasHeader($response, 'X-Custom-Header');
    }

    public function testAssertResponseHeaderContains(): void
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $this->assertResponseHeaderContains($response, 'Content-Type', 'application/json');
    }

    public function testAssertRequestMethod(): void
    {
        $request = Request::create('/', 'POST');
        $this->assertRequestMethod($request, 'POST');
    }

    public function testAssertRequestHasHeader(): void
    {
        $request = Request::create('/');
        $request->headers->set('X-Custom-Header', 'value');
        $this->assertRequestHasHeader($request, 'X-Custom-Header');
    }

    public function testAssertRequestHasParameter(): void
    {
        $request = Request::create('/', 'GET', ['param' => 'value']);
        $this->assertRequestHasParameter($request, 'param');
    }
}
