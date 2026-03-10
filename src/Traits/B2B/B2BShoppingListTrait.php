<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListCollection;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListEntity;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingListLineItem\ShoppingListLineItemCollection;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Trait for B2B shopping list management operations and assertions.
 */
trait B2BShoppingListTrait
{
    use KernelTestBehaviour;

    protected function convertShoppingListToCart(string $shoppingListId, SalesChannelContext $context): Cart
    {
        $shoppingList = $this->getShoppingListById($shoppingListId, $context->getContext());
        $cartService = static::getContainer()->get(CartService::class);

        $cart = $cartService->createNew($context->getToken());

        if (! $shoppingList->getLineItems() instanceof ShoppingListLineItemCollection || count($shoppingList->getLineItems()) === 0) {
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

        return $cartService->recalculate($cart, $context);
    }

    protected function addShoppingListToCart(string $shoppingListId, Cart $cart, SalesChannelContext $context): Cart
    {
        $shoppingList = $this->getShoppingListById($shoppingListId, $context->getContext());
        $cartService = static::getContainer()->get(CartService::class);

        if (! $shoppingList->getLineItems() instanceof ShoppingListLineItemCollection) {
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

        return $cartService->recalculate($cart, $context);
    }

    protected function getShoppingListTotalValue(string $shoppingListId, SalesChannelContext $context): float
    {
        $cart = $this->convertShoppingListToCart($shoppingListId, $context);

        return $cart->getPrice()->getTotalPrice();
    }

    protected function getShoppingListItemCount(string $shoppingListId, ?Context $context = null): int
    {
        $shoppingList = $this->getShoppingListById($shoppingListId, $context);

        return $shoppingList->getLineItems() instanceof ShoppingListLineItemCollection ? count($shoppingList->getLineItems()) : 0;
    }

    protected function canEmployeeAccessShoppingList(string $employeeId, string $shoppingListId, ?Context $context = null): bool
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getShoppingListRepository();

        $criteria = new Criteria([$shoppingListId]);
        $criteria->addAssociation('employee');
        $criteria->addAssociation('sharedWith');

        $shoppingList = $repository->search($criteria, $context)->first();

        if (! $shoppingList instanceof ShoppingListEntity) {
            return false;
        }

        if ($shoppingList->getEmployeeId() === $employeeId) {
            return true;
        }

        if (! method_exists($shoppingList, 'getSharedWith')) {
            return false;
        }

        $sharedWith = $shoppingList->getSharedWith();
        if ($sharedWith) {
            foreach ($sharedWith as $shared) {
                if (method_exists($shared, 'getEmployeeId') && $shared->getEmployeeId() === $employeeId) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function shareShoppingListWithEmployee(string $shoppingListId, string $employeeId, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $this->getShoppingListRepository()->update([
            [
                'id' => $shoppingListId,
                'sharedWith' => [
                    ['employeeId' => $employeeId],
                ],
            ],
        ], $context);
    }

    /**
     * @return array<int, EmployeeEntity>
     */
    protected function getShoppingListSharedEmployees(string $shoppingListId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getShoppingListRepository();

        $criteria = new Criteria([$shoppingListId]);
        $criteria->addAssociation('sharedWith.employee');

        $shoppingList = $repository->search($criteria, $context)->first();

        if (! $shoppingList instanceof ShoppingListEntity || ! method_exists($shoppingList, 'getSharedWith') || ! $shoppingList->getSharedWith()) {
            return [];
        }

        $employees = [];
        foreach ($shoppingList->getSharedWith() as $shared) {
            if (method_exists($shared, 'getEmployee') && $shared->getEmployee()) {
                $employees[] = $shared->getEmployee();
            }
        }

        return $employees;
    }

    /**
     * @return array<int, ShoppingListEntity>
     */
    protected function getEmployeeAccessibleShoppingLists(string $employeeId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getShoppingListRepository();

        // Get owned lists
        $ownedCriteria = new Criteria();
        $ownedCriteria->addFilter(new EqualsFilter('employeeId', $employeeId));
        /** @var array<string, ShoppingListEntity> $ownedLists */
        $ownedLists = $repository->search($ownedCriteria, $context)->getElements();

        // Get shared lists
        $sharedCriteria = new Criteria();
        $sharedCriteria->addFilter(new EqualsFilter('sharedWith.employeeId', $employeeId));
        $sharedCriteria->addAssociation('employee');
        /** @var array<string, ShoppingListEntity> $sharedLists */
        $sharedLists = $repository->search($sharedCriteria, $context)->getElements();

        return array_merge(array_values($ownedLists), array_values($sharedLists));
    }

    private function getShoppingListById(string $shoppingListId, ?Context $context = null): ShoppingListEntity
    {
        $context ??= Context::createCLIContext();
        $repository = $this->getShoppingListRepository();

        $criteria = new Criteria([$shoppingListId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');

        $shoppingList = $repository->search($criteria, $context)->first();

        if (! $shoppingList instanceof ShoppingListEntity) {
            throw new \RuntimeException(sprintf('ShoppingList with ID "%s" not found', $shoppingListId));
        }

        return $shoppingList;
    }

    /**
     * @return EntityRepository<ShoppingListCollection>
     */
    private function getShoppingListRepository(): EntityRepository
    {
        return static::getContainer()->get('shopping_list.repository');
    }
}
