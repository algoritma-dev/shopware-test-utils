<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Shopware\Commercial\B2B\EmployeeManagement\Entity\Role\RoleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory for creating roles with permission sets.
 * Pure factory: only creates roles, no business logic.
 */
class RolePermissionFactory
{
    private array $data;

    private array $permissions = [];

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->data = [
            'id' => Uuid::randomHex(),
        ];
    }

    /**
     * Set role name.
     */
    public function withName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    /**
     * Add a single permission by code.
     */
    public function withPermission(string $permissionCode): self
    {
        $this->permissions[] = $permissionCode;

        return $this;
    }

    /**
     * Add multiple permissions.
     */
    public function withPermissions(array $permissionCodes): self
    {
        $this->permissions = array_merge($this->permissions, $permissionCodes);

        return $this;
    }

    /**
     * Add all quote management permissions.
     */
    public function withQuotePermissions(): self
    {
        return $this->withPermissions([
            'quote.create',
            'quote.view',
            'quote.edit',
            'quote.delete',
            'quote.view_all',
        ]);
    }

    /**
     * Add all order approval permissions.
     */
    public function withApprovalPermissions(): self
    {
        return $this->withPermissions([
            'pending_order.approve',
            'pending_order.decline',
            'pending_order.view',
            'pending_order.view_all',
        ]);
    }

    /**
     * Add all budget management permissions.
     */
    public function withBudgetPermissions(): self
    {
        return $this->withPermissions([
            'budget.create',
            'budget.view',
            'budget.edit',
            'budget.delete',
        ]);
    }

    /**
     * Add all shopping list permissions.
     */
    public function withShoppingListPermissions(): self
    {
        return $this->withPermissions([
            'shopping_list.create',
            'shopping_list.view',
            'shopping_list.edit',
            'shopping_list.delete',
            'shopping_list.share',
        ]);
    }

    /**
     * Add all organization unit permissions.
     */
    public function withOrganizationPermissions(): self
    {
        return $this->withPermissions([
            'organization.create',
            'organization.view',
            'organization.edit',
            'organization.delete',
        ]);
    }

    /**
     * Add all employee management permissions.
     */
    public function withEmployeeManagementPermissions(): self
    {
        return $this->withPermissions([
            'employee.create',
            'employee.view',
            'employee.edit',
            'employee.delete',
            'employee.invite',
        ]);
    }

    /**
     * Create a role with admin-level permissions (all permissions).
     */
    public function withAdminPermissions(): self
    {
        return $this
            ->withQuotePermissions()
            ->withApprovalPermissions()
            ->withBudgetPermissions()
            ->withShoppingListPermissions()
            ->withOrganizationPermissions()
            ->withEmployeeManagementPermissions();
    }

    /**
     * Create a role with read-only permissions.
     */
    public function withReadOnlyPermissions(): self
    {
        return $this->withPermissions([
            'quote.view',
            'pending_order.view',
            'budget.view',
            'shopping_list.view',
            'organization.view',
            'employee.view',
        ]);
    }

    /**
     * Create the role with permissions.
     */
    public function create(?Context $context = null): RoleEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_role.repository');

        // Resolve permission IDs
        $permissionIds = $this->resolvePermissionIds($context);

        $this->data['permissions'] = array_map(fn ($id): array => ['id' => $id], $permissionIds);

        $repository->create([$this->data], $context);

        return $this->load($this->data['id'], $context);
    }

    /**
     * Create predefined role: Admin.
     */
    public static function createAdmin(ContainerInterface $container, ?Context $context = null): RoleEntity
    {
        return (new self($container))
            ->withName('Admin')
            ->withAdminPermissions()
            ->create($context);
    }

    /**
     * Create predefined role: Manager.
     */
    public static function createManager(ContainerInterface $container, ?Context $context = null): RoleEntity
    {
        return (new self($container))
            ->withName('Manager')
            ->withQuotePermissions()
            ->withApprovalPermissions()
            ->withBudgetPermissions()
            ->create($context);
    }

    /**
     * Create predefined role: Employee.
     */
    public static function createEmployee(ContainerInterface $container, ?Context $context = null): RoleEntity
    {
        return (new self($container))
            ->withName('Employee')
            ->withPermissions([
                'quote.create',
                'quote.view',
                'shopping_list.create',
                'shopping_list.view',
                'shopping_list.edit',
            ])
            ->create($context);
    }

    /**
     * Create predefined role: Viewer.
     */
    public static function createViewer(ContainerInterface $container, ?Context $context = null): RoleEntity
    {
        return (new self($container))
            ->withName('Viewer')
            ->withReadOnlyPermissions()
            ->create($context);
    }

    private function resolvePermissionIds(Context $context): array
    {
        if ($this->permissions === []) {
            return [];
        }

        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_permission.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('code', $this->permissions));

        $result = $repository->search($criteria, $context);

        return array_map(fn (Entity $permission) => $permission->getId(), array_values($result->getElements()));
    }

    private function load(string $id, Context $context): RoleEntity
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_role.repository');

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('permissions');

        $role = $repository->search($criteria, $context)->first();

        if (! $role) {
            throw new \RuntimeException(sprintf('Role with ID "%s" not found', $id));
        }

        return $role;
    }
}
