<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Doctrine\DBAL\Connection;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory as ShopwareSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory for creating B2B-aware SalesChannelContext instances for testing.
 * Pure factory: only creates contexts, no business logic.
 */
class B2BContextFactory
{
    private ?string $employeeId = null;

    private ?string $customerId = null;

    private ?string $organizationId = null;

    private ?string $roleId = null;

    private ?string $salesChannelId = null;

    private ?string $languageId = null;

    private ?string $currencyId = null;

    /**
     * @var array<string>
     */
    private array $permissions = [];

    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Set the employee for this context.
     */
    public function withEmployee(string|EmployeeEntity $employee): self
    {
        $this->employeeId = $employee instanceof EmployeeEntity ? $employee->getId() : $employee;

        return $this;
    }

    /**
     * Set the business partner customer.
     */
    public function withCustomer(string|CustomerEntity $customer): self
    {
        $this->customerId = $customer instanceof CustomerEntity ? $customer->getId() : $customer;

        return $this;
    }

    /**
     * Set the organization unit context.
     */
    public function withOrganization(string $organizationId): self
    {
        $this->organizationId = $organizationId;

        return $this;
    }

    /**
     * Set the employee role.
     */
    public function withRole(string $roleId): self
    {
        $this->roleId = $roleId;

        return $this;
    }

    /**
     * Set the sales channel.
     */
    public function withSalesChannel(string $salesChannelId): self
    {
        $this->salesChannelId = $salesChannelId;

        return $this;
    }

    /**
     * Set the language.
     */
    public function withLanguage(string $languageId): self
    {
        $this->languageId = $languageId;

        return $this;
    }

    /**
     * Set the currency.
     */
    public function withCurrency(string $currencyId): self
    {
        $this->currencyId = $currencyId;

        return $this;
    }

    /**
     * Add B2B permissions to the context.
     *
     * @param array<string> $permissions
     */
    public function withPermissions(array $permissions): self
    {
        $this->permissions = array_merge($this->permissions, $permissions);

        return $this;
    }

    /**
     * Create the SalesChannelContext with all configured B2B settings.
     */
    public function create(): SalesChannelContext
    {
        /** @var ShopwareSalesChannelContextFactory $factory */
        $factory = $this->container->get(ShopwareSalesChannelContextFactory::class);

        $options = [];

        if ($this->employeeId) {
            $options['employeeId'] = $this->employeeId;
        }

        if ($this->customerId) {
            $options['customerId'] = $this->customerId;
        }

        if ($this->organizationId) {
            $options['organizationId'] = $this->organizationId;
        }

        if ($this->roleId) {
            $options['roleId'] = $this->roleId;
        }

        if ($this->languageId) {
            $options['languageId'] = $this->languageId;
        }

        if ($this->currencyId) {
            $options['currencyId'] = $this->currencyId;
        }

        if ($this->permissions !== []) {
            $options['permissions'] = $this->permissions;
        }

        $salesChannelId = $this->salesChannelId ?? $this->getDefaultSalesChannelId();
        $token = Uuid::randomHex();

        return $factory->create($token, $salesChannelId, $options);
    }

    /**
     * Create a guest B2B context (employee not authenticated).
     */
    public function createGuest(): SalesChannelContext
    {
        $this->employeeId = null;

        return $this->create();
    }

    private function getDefaultSalesChannelId(): string
    {
        $connection = $this->container->get(Connection::class);
        $result = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM sales_channel LIMIT 1');

        if (! $result) {
            throw new \RuntimeException('No sales channel found in database');
        }

        return $result;
    }
}
