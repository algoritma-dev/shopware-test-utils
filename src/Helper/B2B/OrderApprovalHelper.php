<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\OrderApproval\Domain\CartToPendingOrder\PendingOrderRequestedRoute;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderApprovalHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * @param array<string, mixed> $data
     */
    public function requestPendingOrder(SalesChannelContext $context, CustomerEntity $customer, array $data = []): PendingOrderEntity
    {
        /** @var PendingOrderRequestedRoute $route */
        $route = $this->container->get(PendingOrderRequestedRoute::class);

        $dataBag = new RequestDataBag($data);
        // @phpstan-ignore-next-line
        $response = $route->request($context, $customer, $dataBag);

        return $response->getPendingOrder();
    }
}
