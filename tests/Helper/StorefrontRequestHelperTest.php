<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\StorefrontRequestHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class StorefrontRequestHelperTest extends TestCase
{
    public function testLogin(): void
    {
        $browser = $this->createMock(KernelBrowser::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $response = $this->createStub(Response::class);

        $browser->expects($this->once())->method('request')->with(
            'POST',
            '/account/login',
            $this->anything()
        );

        $browser->method('getResponse')->willReturn($response);
        $response->method('getStatusCode')->willReturn(302);

        $helper = new StorefrontRequestHelper($browser, $salesChannelContext);
        $helper->login('test@example.com', 'password');
    }

    public function testAddToCart(): void
    {
        $browser = $this->createMock(KernelBrowser::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        $response = $this->createStub(Response::class);

        $browser->expects($this->once())->method('request')->with(
            'POST',
            '/checkout/line-item/add',
            $this->anything()
        );

        $browser->method('getResponse')->willReturn($response);
        $response->method('getStatusCode')->willReturn(200);

        $helper = new StorefrontRequestHelper($browser, $salesChannelContext);
        $helper->addToCart('product-id');
    }
}
