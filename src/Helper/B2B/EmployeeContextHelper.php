<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\B2BContextFactory;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for creating authenticated employee contexts.
 * Pure helper: orchestrates employee context creation, delegates to factory.
 */
class EmployeeContextHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Create an authenticated context for an employee by ID.
     */
    public function createContextForEmployee(string $employeeId, ?string $salesChannelId = null): SalesChannelContext
    {
        $employee = $this->loadEmployee($employeeId);

        if (! $employee instanceof EmployeeEntity) {
            throw new \RuntimeException(sprintf('Employee with ID "%s" not found', $employeeId));
        }

        return $this->createContextFromEmployee($employee, $salesChannelId);
    }

    /**
     * Create an authenticated context for an employee by email.
     */
    public function createContextForEmployeeEmail(string $email, ?string $salesChannelId = null): SalesChannelContext
    {
        $employee = $this->loadEmployeeByEmail($email);

        if (! $employee instanceof EmployeeEntity) {
            throw new \RuntimeException(sprintf('Employee with email "%s" not found', $email));
        }

        return $this->createContextFromEmployee($employee, $salesChannelId);
    }

    /**
     * Create context from an EmployeeEntity.
     */
    public function createContextFromEmployee(EmployeeEntity $employee, ?string $salesChannelId = null): SalesChannelContext
    {
        $factory = new B2BContextFactory($this->container);

        $factory->withEmployee($employee->getId());

        if ($employee->getBusinessPartnerCustomerId()) {
            $factory->withCustomer($employee->getBusinessPartnerCustomerId());
        }

        if ($employee->getRoleId()) {
            $factory->withRole($employee->getRoleId());
        }

        if ($employee->getOrganizationId()) {
            $factory->withOrganization($employee->getOrganizationId());
        }

        if ($salesChannelId) {
            $factory->withSalesChannel($salesChannelId);
        }

        return $factory->create();
    }

    private function loadEmployee(string $employeeId): ?EmployeeEntity
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_employee.repository');

        $criteria = new Criteria([$employeeId]);
        $criteria->addAssociation('businessPartnerCustomer');
        $criteria->addAssociation('role');
        $criteria->addAssociation('organization');

        return $repository->search($criteria, Context::createDefaultContext())->first();
    }

    private function loadEmployeeByEmail(string $email): ?EmployeeEntity
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_employee.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $criteria->addAssociation('businessPartnerCustomer');
        $criteria->addAssociation('role');
        $criteria->addAssociation('organization');

        return $repository->search($criteria, Context::createDefaultContext())->first();
    }
}
