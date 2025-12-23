<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;

/**
 * Helper for product-related operations and assertions.
 */
class ProductHelper
{
    // --- Product Assertions ---

    /**
     * Assert that a product is in stock.
     */
    public function assertProductInStock(ProductEntity $product, int $minStock = 1): void
    {
        $stock = $product->getStock();
        assert($stock >= $minStock, sprintf('Product has stock %d, expected at least %d', $stock, $minStock));
    }

    /**
     * Assert that a product is out of stock.
     */
    public function assertProductOutOfStock(ProductEntity $product): void
    {
        $stock = $product->getStock();
        assert($stock === 0, sprintf('Product has stock %d, expected 0', $stock));
    }

    /**
     * Assert that a product is active.
     */
    public function assertProductActive(ProductEntity $product): void
    {
        assert($product->getActive(), 'Product is not active');
    }

    /**
     * Assert that a product is inactive.
     */
    public function assertProductInactive(ProductEntity $product): void
    {
        assert(! $product->getActive(), 'Product is active but should be inactive');
    }

    /**
     * Assert that a product has a specific category.
     */
    public function assertProductHasCategory(ProductEntity $product, string $categoryId): void
    {
        $categories = $product->getCategoryTree();
        assert($categories !== null, 'Product has no category tree');
        assert(in_array($categoryId, $categories, true), sprintf('Product does not have category %s', $categoryId));
    }

    /**
     * Assert that a product price is within a specific range.
     */
    public function assertPriceInRange(ProductEntity $product, float $min, float $max): void
    {
        $price = $product->getCurrencyPrice(Defaults::CURRENCY);
        assert($price instanceof Price, 'Product has no price for default currency');

        $gross = $price->getGross();
        assert($gross >= $min, sprintf('Price %.2f is below minimum %.2f', $gross, $min));
        assert($gross <= $max, sprintf('Price %.2f is above maximum %.2f', $gross, $max));
    }

    /**
     * Assert that a product price equals expected value.
     */
    public function assertPriceEquals(float $expected, ProductEntity $product): void
    {
        $price = $product->getCurrencyPrice(Defaults::CURRENCY);
        assert($price instanceof Price, 'Product has no price for default currency.');
        assert(abs($price->getGross() - $expected) < 0.01, sprintf('Product price %.2f does not match expected value %.2f.', $price->getGross(), $expected));
    }
}
