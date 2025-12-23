<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class StorefrontRequestHelper
{
    public function __construct(private readonly KernelBrowser $browser) {}

    public function login(string $email, string $password): void
    {
        $this->browser->request(
            'POST',
            '/account/login',
            [
                'username' => $email,
                'password' => $password,
            ]
        );

        $response = $this->browser->getResponse();
        if ($response->getStatusCode() !== 302) {
            throw new \RuntimeException('Login failed. Status code: ' . $response->getStatusCode());
        }
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

    private function assertSuccessOrRedirect(): void
    {
        $statusCode = $this->browser->getResponse()->getStatusCode();
        if ($statusCode >= 400) {
            throw new \RuntimeException('Request failed with status code: ' . $statusCode);
        }
    }
}
