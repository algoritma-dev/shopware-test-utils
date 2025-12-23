<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\OrderApprovalHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\OrderApproval\Domain\CartToPendingOrder\PendingOrderRequestedResponse;
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
    }

    public function testRequestPendingOrder(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $route = $this->createMock(PendingOrderRequestedRoute::class);
        $response = $this->createMock(PendingOrderRequestedResponse::class);
        $pendingOrder = new PendingOrderEntity();
        $context = $this->createMock(SalesChannelContext::class);
        $customer = $this->createMock(CustomerEntity::class);

        $container->method('get')->willReturn($route);
        $route->method('request')->willReturn($response);
        $response->method('getPendingOrder')->willReturn($pendingOrder);

        $helper = new OrderApprovalHelper($container);
        $result = $helper->requestPendingOrder($context, $customer);

        $this->assertSame($pendingOrder, $result);
    }
}
