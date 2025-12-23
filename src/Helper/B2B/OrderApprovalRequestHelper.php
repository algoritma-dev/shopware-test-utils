<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\PendingOrderFactory;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for requesting order approvals from storefront.
 * Pure helper: orchestrates approval requests, delegates creation to factory.
 */
class OrderApprovalRequestHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Request approval for a cart/order.
     */
    public function requestApproval(
        Cart $cart,
        SalesChannelContext $context,
        string $employeeId,
        ?string $approvalRuleId = null
    ): string {
        return PendingOrderFactory::fromCart(
            $this->container,
            $cart,
            $context,
            $employeeId,
            $approvalRuleId
        )->getId();
    }

    /**
     * Request approval with reason.
     */
    public function requestApprovalWithReason(
        Cart $cart,
        SalesChannelContext $context,
        string $employeeId,
        string $reason,
        ?string $approvalRuleId = null
    ): string {
        $factory = new PendingOrderFactory($this->container);
        $factory->withCart($cart, $context)
            ->withEmployee($employeeId)
            ->withReason($reason);

        if ($approvalRuleId) {
            $factory->withApprovalRule($approvalRuleId);
        }

        if ($context->getCustomer() instanceof CustomerEntity) {
            $factory->withCustomer($context->getCustomer()->getId());
        }

        return $factory->create($context->getContext())->getId();
    }
}
