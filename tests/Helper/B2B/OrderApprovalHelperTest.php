<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\OrderApprovalHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\OrderApproval\Domain\CartToPendingOrder\PendingOrderRequestedRoute;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderApprovalHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(PendingOrderEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
        if (! class_exists('Shopware\Commercial\B2B\OrderApproval\Domain\CartToPendingOrder\PendingOrderRequestedResponse')) {
            $this->markTestSkipped('PendingOrderRequestedResponse class not found.');
        }
    }

    public function testRequestPendingOrder(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $route = $this->createStub(PendingOrderRequestedRoute::class);
        /** @phpstan-ignore-next-line */
        $response = $this->createStub('Shopware\Commercial\B2B\OrderApproval\Domain\CartToPendingOrder\PendingOrderRequestedResponse');
        $pendingOrder = new PendingOrderEntity();
        $context = $this->createStub(SalesChannelContext::class);
        $customer = $this->createStub(CustomerEntity::class);

        $container->method('get')->willReturn($route);
        $route->method('request')->willReturn($response);
        /** @phpstan-ignore-next-line */
        $response->method('getPendingOrder')->willReturn($pendingOrder);

        $helper = new OrderApprovalHelper($container);
        $result = $helper->requestPendingOrder($context, $customer);

        $this->assertSame($pendingOrder, $result);
    }
}
