<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Permission\PermissionDefinition;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Role\RoleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory for creating roles with permission sets.
 * Pure factory: only creates roles, no business logic.
 */
class RolePermissionFactory extends AbstractFactory
{
    /**
     * @var array<string>
     */
    private array $permissions = [];

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->data = [
            'id' => Uuid::randomHex(),
        ];
    }

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
     *
     * @param array<string> $permissionCodes
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
     * Create predefined role: Admin.
     */
    public static function createAdmin(ContainerInterface $container, ?Context $context = null): RoleEntity
    {
        $role = (new self($container))
            ->withName('Admin')
            ->withAdminPermissions()
            ->create($context);

        \assert($role instanceof RoleEntity);

        return $role;
    }

    /**
     * Create predefined role: Manager.
     */
    public static function createManager(ContainerInterface $container, ?Context $context = null): RoleEntity
    {
        $role = (new self($container))
            ->withName('Manager')
            ->withQuotePermissions()
            ->withApprovalPermissions()
            ->withBudgetPermissions()
            ->create($context);

        \assert($role instanceof RoleEntity);

        return $role;
    }

    /**
     * Create predefined role: Employee.
     */
    public static function createEmployee(ContainerInterface $container, ?Context $context = null): RoleEntity
    {
        $role = (new self($container))
            ->withName('Employee')
            ->withPermissions([
                'quote.create',
                'quote.view',
                'shopping_list.create',
                'shopping_list.view',
                'shopping_list.edit',
            ])
            ->create($context);

        \assert($role instanceof RoleEntity);

        return $role;
    }

    /**
     * Create predefined role: Viewer.
     */
    public static function createViewer(ContainerInterface $container, ?Context $context = null): RoleEntity
    {
        $role = (new self($container))
            ->withName('Viewer')
            ->withReadOnlyPermissions()
            ->create($context);

        \assert($role instanceof RoleEntity);

        return $role;
    }

    protected function getRepositoryName(): string
    {
        return 'b2b_permission.repository';
    }

    protected function getEntityName(): string
    {
        return PermissionDefinition::ENTITY_NAME;
    }
}
