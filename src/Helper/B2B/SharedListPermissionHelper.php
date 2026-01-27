<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListCollection;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for testing shopping list sharing and permissions.
 */
class SharedListPermissionHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Check if employee can access shopping list.
     */
    public function canAccess(string $employeeId, string $shoppingListId, ?Context $context = null): bool
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<ShoppingListCollection> $repository */
        $repository = $this->container->get('shopping_list.repository');

        $criteria = new Criteria([$shoppingListId]);
        $criteria->addAssociation('employee');
        $criteria->addAssociation('sharedWith');

        $shoppingList = $repository->search($criteria, $context)->first();

        if (! $shoppingList instanceof ShoppingListEntity) {
            return false;
        }

        // Owner can always access
        if ($shoppingList->getEmployeeId() === $employeeId) {
            return true;
        }

        // Check if shared with this employee
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

    /**
     * Share shopping list with employee.
     */
    public function shareWith(string $shoppingListId, string $employeeId, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<ShoppingListCollection> $repository */
        $repository = $this->container->get('shopping_list.repository');

        $repository->update([
            [
                'id' => $shoppingListId,
                'sharedWith' => [
                    ['employeeId' => $employeeId],
                ],
            ],
        ], $context);
    }

    /**
     * Get all employees who have access to shopping list.
     *
     * @return array<int, EmployeeEntity>
     */
    public function getSharedEmployees(string $shoppingListId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<ShoppingListCollection> $repository */
        $repository = $this->container->get('shopping_list.repository');

        $criteria = new Criteria([$shoppingListId]);
        $criteria->addAssociation('sharedWith.employee');

        $shoppingList = $repository->search($criteria, $context)->first();

        if (! $shoppingList instanceof ShoppingListEntity) {
            return [];
        }

        if (! method_exists($shoppingList, 'getSharedWith') || ! $shoppingList->getSharedWith()) {
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
     * Get all shopping lists accessible by employee.
     *
     * @return array<int, ShoppingListEntity>
     */
    public function getAccessibleLists(string $employeeId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<ShoppingListCollection> $repository */
        $repository = $this->container->get('shopping_list.repository');

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
}
