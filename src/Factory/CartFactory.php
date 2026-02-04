<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory for creating and building carts with fluent interface.
 * Follows the Builder pattern for complex cart construction.
 */
class CartFactory
{
    private readonly CartService $cartService;

    private Cart $cart;

    public function __construct(private readonly ContainerInterface $container, private readonly SalesChannelContext $context)
    {
        $this->cartService = $this->container->get(CartService::class);
        $this->cart = $this->cartService->getCart($this->context->getToken(), $this->context);
    }

    /**
     * Adds a product to the cart.
     */
    public function withProduct(string $productId, int $quantity = 1): self
    {
        $productLineItem = new LineItem($productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, $quantity);

        $this->cart = $this->cartService->add($this->cart, $productLineItem, $this->context);

        return $this;
    }

    /**
     * Adds a promotion code to the cart.
     */
    public function withPromotion(string $code): self
    {
        $promotionLineItem = new LineItem($code, LineItem::PROMOTION_LINE_ITEM_TYPE, $code, 1);

        $this->cart = $this->cartService->add($this->cart, $promotionLineItem, $this->context);

        return $this;
    }

    /**
     * Adds a custom line item to the cart.
     */
    public function withCustomLineItem(string $label, float $price, int $quantity = 1, string $type = LineItem::CUSTOM_LINE_ITEM_TYPE): self
    {
        $lineItem = new LineItem(Uuid::randomHex(), $type, null, $quantity);
        $lineItem->setLabel($label);
        $lineItem->setPrice(new CalculatedPrice(
            $price,
            $price * $quantity,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));

        $this->cart = $this->cartService->add($this->cart, $lineItem, $this->context);

        return $this;
    }

    /**
     * Add a line item.
     */
    public function withLineItem(LineItem $lineItem): self
    {
        $this->cart = $this->cartService->add($this->cart, $lineItem, $this->context);

        return $this;
    }

    /**
     * Add multiple line items.
     *
     * @param array<LineItem> $lineItems
     */
    public function withLineItems(array $lineItems): self
    {
        $this->cart = $this->cartService->add($this->cart, $lineItems, $this->context);

        return $this;
    }

    /**
     * Get the current cart object for advanced manipulation.
     */
    public function getCart(): Cart
    {
        return $this->cart;
    }

    /**
     * Creates and returns the fully calculated cart.
     * This is the final method to call after building the cart.
     */
    public function create(): Cart
    {
        return $this->cart;
    }
}
