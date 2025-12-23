<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\B2BContextFactory;
use Shopware\Commercial\B2B\OrganizationUnit\Entity\OrganizationEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
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

        if ($organization->getCustomerId()) {
            $factory->withCustomer($organization->getCustomerId());
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

        return $this->createForOrganization($organization->getId(), null, $salesChannelId);
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
        $employeeId = $currentContext->getCustomer()?->getExtension('employee')?->getId();
        if ($employeeId) {
            $factory->withEmployee($employeeId);
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
     * @return OrganizationEntity[]
     */
    public function getCustomerOrganizations(string $customerId): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_components_organization.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addAssociation('employees');
        $criteria->addAssociation('paymentMethods');
        $criteria->addAssociation('shippingMethods');

        $result = $repository->search($criteria, Context::createDefaultContext());

        return array_values($result->getElements());
    }

    /**
     * Check if employee belongs to organization.
     */
    public function employeeBelongsToOrganization(string $employeeId, string $organizationId): bool
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_employee.repository');

        $criteria = new Criteria([$employeeId]);
        $employee = $repository->search($criteria, Context::createDefaultContext())->first();

        if (! $employee) {
            return false;
        }

        return $employee->getOrganizationId() === $organizationId;
    }

    private function loadOrganization(string $organizationId): ?OrganizationEntity
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_components_organization.repository');

        $criteria = new Criteria([$organizationId]);
        $criteria->addAssociation('customer');
        $criteria->addAssociation('employees');
        $criteria->addAssociation('paymentMethods');
        $criteria->addAssociation('shippingMethods');
        $criteria->addAssociation('defaultShippingAddress');
        $criteria->addAssociation('defaultBillingAddress');

        return $repository->search($criteria, Context::createDefaultContext())->first();
    }

    private function findCustomerDefaultOrganization(string $customerId): ?OrganizationEntity
    {
        $organizations = $this->getCustomerOrganizations($customerId);

        // Return first organization as default
        return $organizations === [] ? null : $organizations[0];
    }
}
