<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\SharedListPermissionHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\ShoppingListCartConverter;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait B2BShoppingListTrait
{
    use KernelTestBehaviour;

    private ?ShoppingListCartConverter $b2bShoppingListCartConverterInstance = null;

    private ?SharedListPermissionHelper $b2bSharedListPermissionHelperInstance = null;

    protected function getB2bShoppingListCartConverter(): ShoppingListCartConverter
    {
        if (! $this->b2bShoppingListCartConverterInstance instanceof ShoppingListCartConverter) {
            $this->b2bShoppingListCartConverterInstance = new ShoppingListCartConverter(static::getContainer());
        }

        return $this->b2bShoppingListCartConverterInstance;
    }

    protected function getB2bSharedListPermissionHelper(): SharedListPermissionHelper
    {
        if (! $this->b2bSharedListPermissionHelperInstance instanceof SharedListPermissionHelper) {
            $this->b2bSharedListPermissionHelperInstance = new SharedListPermissionHelper(static::getContainer());
        }

        return $this->b2bSharedListPermissionHelperInstance;
    }

    protected function b2bShoppingListConvertToCart(string $shoppingListId, SalesChannelContext $context): Cart
    {
        return $this->getB2bShoppingListCartConverter()->convertToCart($shoppingListId, $context);
    }

    protected function b2bShoppingListAddToCart(string $shoppingListId, Cart $cart, SalesChannelContext $context): Cart
    {
        return $this->getB2bShoppingListCartConverter()->addToCart($shoppingListId, $cart, $context);
    }

    protected function b2bShoppingListGetTotalValue(string $shoppingListId, SalesChannelContext $context): float
    {
        return $this->getB2bShoppingListCartConverter()->getTotalValue($shoppingListId, $context);
    }

    protected function b2bShoppingListGetItemCount(string $shoppingListId, ?Context $context = null): int
    {
        return $this->getB2bShoppingListCartConverter()->getItemCount($shoppingListId, $context);
    }

    protected function b2bShoppingListCanAccess(string $employeeId, string $shoppingListId, ?Context $context = null): bool
    {
        return $this->getB2bSharedListPermissionHelper()->canAccess($employeeId, $shoppingListId, $context);
    }

    protected function b2bShoppingListShareWith(string $shoppingListId, string $employeeId, ?Context $context = null): void
    {
        $this->getB2bSharedListPermissionHelper()->shareWith($shoppingListId, $employeeId, $context);
    }

    /**
     * @return array<int, EmployeeEntity>
     */
    protected function b2bShoppingListGetSharedEmployees(string $shoppingListId, ?Context $context = null): array
    {
        return $this->getB2bSharedListPermissionHelper()->getSharedEmployees($shoppingListId, $context);
    }

    /**
     * @return array<int, ShoppingListEntity>
     */
    protected function b2bShoppingListGetAccessibleLists(string $employeeId, ?Context $context = null): array
    {
        return $this->getB2bSharedListPermissionHelper()->getAccessibleLists($employeeId, $context);
    }
}
