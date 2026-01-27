<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\B2BContextFactory;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeCollection;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\OrganizationUnit\Entity\OrganizationEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for managing contexts with organization unit associations.
 * Pure helper: orchestrates organization context operations, delegates creation to factory.
 */
class OrganizationContextHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Create a context scoped to a specific organization unit.
     */
    public function createForOrganization(
        string $organizationId,
        ?string $employeeId = null,
        ?string $salesChannelId = null
    ): SalesChannelContext {
        $organization = $this->loadOrganization($organizationId);

        if (! $organization instanceof OrganizationEntity) {
            throw new \RuntimeException(sprintf('Organization with ID "%s" not found', $organizationId));
        }

        $factory = new B2BContextFactory($this->container);
        $factory->withOrganization($organizationId);

        if ($organization->customerId) {
            $factory->withCustomer($organization->customerId);
        }

        if ($employeeId) {
            $factory->withEmployee($employeeId);
        }

        if ($salesChannelId) {
            $factory->withSalesChannel($salesChannelId);
        }

        return $factory->create();
    }

    /**
     * Create a context for a customer's default organization.
     */
    public function createForCustomerDefaultOrganization(
        string $customerId,
        ?string $salesChannelId = null
    ): SalesChannelContext {
        $organization = $this->findCustomerDefaultOrganization($customerId);

        if (! $organization instanceof OrganizationEntity) {
            throw new \RuntimeException(sprintf('No default organization found for customer "%s"', $customerId));
        }

        return $this->createForOrganization($organization->id, null, $salesChannelId);
    }

    /**
     * Switch organization context (simulate employee switching organization units).
     */
    public function switchOrganization(
        SalesChannelContext $currentContext,
        string $newOrganizationId
    ): SalesChannelContext {
        $organization = $this->loadOrganization($newOrganizationId);

        if (! $organization instanceof OrganizationEntity) {
            throw new \RuntimeException(sprintf('Organization with ID "%s" not found', $newOrganizationId));
        }

        $factory = new B2BContextFactory($this->container);
        $factory->withOrganization($newOrganizationId);
        $factory->withSalesChannel($currentContext->getSalesChannelId());

        // Preserve employee if present
        /** @var EmployeeEntity|null $employee */
        $employee = $currentContext->getCustomer()?->getExtension('employee');
        if ($employee) {
            $factory->withEmployee($employee->getId());
        }

        // Preserve customer
        if ($currentContext->getCustomer() instanceof CustomerEntity) {
            $factory->withCustomer($currentContext->getCustomer()->getId());
        }

        return $factory->create();
    }

    /**
     * Get all organizations for a customer.
     *
     * @return array<OrganizationEntity>
     */
    public function getCustomerOrganizations(string $customerId): array
    {
        /** @var EntityRepository<EntityCollection<OrganizationEntity>> $repository */
        $repository = $this->container->get('b2b_components_organization.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addAssociation('employees');
        $criteria->addAssociation('paymentMethods');
        $criteria->addAssociation('shippingMethods');

        $result = $repository->search($criteria, Context::createCLIContext());

        /** @var array<string, OrganizationEntity> $elements */
        $elements = $result->getElements();

        return array_values($elements);
    }

    /**
     * Check if employee belongs to organization.
     */
    public function employeeBelongsToOrganization(string $employeeId, string $organizationId): bool
    {
        /** @var EntityRepository<EmployeeCollection> $repository */
        $repository = $this->container->get('b2b_employee.repository');

        $criteria = new Criteria([$employeeId]);
        $employee = $repository->search($criteria, Context::createCLIContext())->first();

        if (! $employee instanceof EmployeeEntity) {
            return false;
        }

        if (! method_exists($employee, 'getOrganizationId')) {
            return false;
        }

        return $employee->getOrganizationId() === $organizationId;
    }

    private function loadOrganization(string $organizationId): ?OrganizationEntity
    {
        /** @var EntityRepository<EntityCollection<OrganizationEntity>> $repository */
        $repository = $this->container->get('b2b_components_organization.repository');

        $criteria = new Criteria([$organizationId]);
        $criteria->addAssociation('customer');
        $criteria->addAssociation('employees');
        $criteria->addAssociation('paymentMethods');
        $criteria->addAssociation('shippingMethods');
        $criteria->addAssociation('defaultShippingAddress');
        $criteria->addAssociation('defaultBillingAddress');

        $entity = $repository->search($criteria, Context::createCLIContext())->first();

        return $entity instanceof OrganizationEntity ? $entity : null;
    }

    private function findCustomerDefaultOrganization(string $customerId): ?OrganizationEntity
    {
        $organizations = $this->getCustomerOrganizations($customerId);

        // Return first organization as default
        return $organizations === [] ? null : $organizations[0];
    }
}
