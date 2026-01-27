<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\StorefrontRequestHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class StorefrontRequestHelperTest extends TestCase
{
    public function testLogin(): void
    {
        $browser = $this->createMock(KernelBrowser::class);
        $response = new Response('', 302);
        $response->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, 'test-token');

        $browser->expects($this->once())->method('request')->with(
            'POST',
            '/account/login',
            $this->anything()
        );

        $container = $this->createStub(ContainerInterface::class);

        $browser->method('getResponse')->willReturn($response);
        $browser->method('getContainer')->willReturn($container);

        $helper = new StorefrontRequestHelper($browser);
        $helper->login('test@example.com', 'password');
    }

    public function testAddToCart(): void
    {
        $browser = $this->createMock(KernelBrowser::class);

        $response = $this->createStub(Response::class);

        $browser->expects($this->once())->method('request')->with(
            'POST',
            '/checkout/line-item/add',
            $this->anything()
        );

        $browser->method('getResponse')->willReturn($response);
        $response->method('getStatusCode')->willReturn(200);

        $helper = new StorefrontRequestHelper($browser);
        $helper->addToCart('product-id');
    }
}
