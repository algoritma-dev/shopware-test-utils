<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

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

        /** @var EntityRepository $repository */
        $repository = $this->container->get('shopping_list.repository');

        $criteria = new Criteria([$shoppingListId]);
        $criteria->addAssociation('employee');
        $criteria->addAssociation('sharedWith');

        $shoppingList = $repository->search($criteria, $context)->first();

        if (! $shoppingList) {
            return false;
        }

        // Owner can always access
        if ($shoppingList->getEmployeeId() === $employeeId) {
            return true;
        }

        // Check if shared with this employee
        $sharedWith = $shoppingList->getSharedWith();
        if ($sharedWith) {
            foreach ($sharedWith as $shared) {
                if ($shared->getEmployeeId() === $employeeId) {
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

        /** @var EntityRepository $repository */
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
     */
    public function getSharedEmployees(string $shoppingListId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('shopping_list.repository');

        $criteria = new Criteria([$shoppingListId]);
        $criteria->addAssociation('sharedWith.employee');

        $shoppingList = $repository->search($criteria, $context)->first();

        if (! $shoppingList || ! $shoppingList->getSharedWith()) {
            return [];
        }

        $employees = [];
        foreach ($shoppingList->getSharedWith() as $shared) {
            if ($shared->getEmployee()) {
                $employees[] = $shared->getEmployee();
            }
        }

        return $employees;
    }

    /**
     * Get all shopping lists accessible by employee.
     */
    public function getAccessibleLists(string $employeeId, ?Context $context = null): array
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('shopping_list.repository');

        // Get owned lists
        $ownedCriteria = new Criteria();
        $ownedCriteria->addFilter(new EqualsFilter('employeeId', $employeeId));

        $ownedLists = $repository->search($ownedCriteria, $context)->getElements();

        // Get shared lists
        $sharedCriteria = new Criteria();
        $sharedCriteria->addFilter(new EqualsFilter('sharedWith.employeeId', $employeeId));
        $sharedCriteria->addAssociation('employee');

        $sharedLists = $repository->search($sharedCriteria, $context)->getElements();

        return array_merge(array_values($ownedLists), array_values($sharedLists));
    }
}
