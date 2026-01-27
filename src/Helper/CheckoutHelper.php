<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CheckoutHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    public function placeOrder(Cart $cart, SalesChannelContext $context): OrderEntity
    {
        /** @var CartService $cartService */
        $cartService = $this->container->get(CartService::class);

        $orderId = $cartService->order($cart, $context, new RequestDataBag());

        return $this->getOrder($orderId, $context->getContext());
    }

    private function getOrder(string $orderId, Context $context): OrderEntity
    {
        /** @var EntityRepository<OrderCollection> $repository */
        $repository = $this->container->get('order.repository');

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('deliveries');

        $order = $repository->search($criteria, $context)->first();

        if (! $order instanceof OrderEntity) {
            throw new \RuntimeException(sprintf('Order with ID "%s" not found', $orderId));
        }

        return $order;
    }
}
