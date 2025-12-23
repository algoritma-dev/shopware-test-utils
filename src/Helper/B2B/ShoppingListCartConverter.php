<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts shopping lists to carts for testing.
 * Handles shopping list line items → cart conversion.
 */
class ShoppingListCartConverter
{
    private readonly CartService $cartService;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->cartService = $this->container->get(CartService::class);
    }

    /**
     * Convert shopping list to cart.
     */
    public function convertToCart(string $shoppingListId, SalesChannelContext $context): Cart
    {
        $shoppingList = $this->loadShoppingList($shoppingListId, $context->getContext());

        $cart = $this->cartService->createNew($context->getToken());

        if (! $shoppingList->getLineItems() || count($shoppingList->getLineItems()) === 0) {
            return $cart;
        }

        foreach ($shoppingList->getLineItems() as $item) {
            $lineItem = new LineItem(
                $item->getId(),
                LineItem::PRODUCT_LINE_ITEM_TYPE,
                $item->getProductId(),
                $item->getQuantity()
            );

            $cart->add($lineItem);
        }

        return $this->cartService->recalculate($cart, $context);
    }

    /**
     * Add shopping list items to existing cart.
     */
    public function addToCart(string $shoppingListId, Cart $cart, SalesChannelContext $context): Cart
    {
        $shoppingList = $this->loadShoppingList($shoppingListId, $context->getContext());

        if (! $shoppingList->getLineItems()) {
            return $cart;
        }

        foreach ($shoppingList->getLineItems() as $item) {
            $lineItem = new LineItem(
                $item->getId(),
                LineItem::PRODUCT_LINE_ITEM_TYPE,
                $item->getProductId(),
                $item->getQuantity()
            );

            $cart->add($lineItem);
        }

        return $this->cartService->recalculate($cart, $context);
    }

    /**
     * Get total value of shopping list.
     */
    public function getTotalValue(string $shoppingListId, SalesChannelContext $context): float
    {
        $cart = $this->convertToCart($shoppingListId, $context);

        return $cart->getPrice()->getTotalPrice();
    }

    /**
     * Get item count in shopping list.
     */
    public function getItemCount(string $shoppingListId, ?Context $context = null): int
    {
        $shoppingList = $this->loadShoppingList($shoppingListId, $context);

        return $shoppingList->getLineItems() ? count($shoppingList->getLineItems()) : 0;
    }

    private function loadShoppingList(string $shoppingListId, ?Context $context): ShoppingListEntity
    {
        $context ??= Context::createDefaultContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('shopping_list.repository');

        $criteria = new Criteria([$shoppingListId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');

        $shoppingList = $repository->search($criteria, $context)->first();

        if (! $shoppingList) {
            throw new \RuntimeException(sprintf('ShoppingList with ID "%s" not found', $shoppingListId));
        }

        return $shoppingList;
    }
}
