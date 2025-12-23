<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\ShoppingListCartConverter;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListEntity;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListLineItemCollection;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListLineItemEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShoppingListCartConverterTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(ShoppingListEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testConvertToCart(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $cartService = $this->createMock(CartService::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $shoppingList = new ShoppingListEntity();
        $context = $this->createMock(SalesChannelContext::class);
        $cart = new Cart('token', 'token');

        $container->method('get')->willReturnMap([
            [CartService::class, 1, $cartService],
            ['shopping_list.repository', 1, $repository],
        ]);

        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($shoppingList);

        $item = new ShoppingListLineItemEntity();
        $item->setId('item-id');
        $item->setProductId('product-id');
        $item->setQuantity(1);
        $shoppingList->setLineItems(new ShoppingListLineItemCollection([$item]));

        $cartService->method('createNew')->willReturn($cart);
        $cartService->method('recalculate')->willReturn($cart);

        $converter = new ShoppingListCartConverter($container);
        $result = $converter->convertToCart('list-id', $context);

        $this->assertSame($cart, $result);
    }
}
