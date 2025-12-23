<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for simulating employee login actions.
 * Pure helper: executes login action, delegates context creation to EmployeeContextHelper.
 */
class EmployeeLoginHelper
{
    private readonly EmployeeContextHelper $contextHelper;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->contextHelper = new EmployeeContextHelper($this->container);
    }

    /**
     * Simulate employee login by email and password.
     */
    public function login(string $email, string $password, ?string $salesChannelId = null): SalesChannelContext
    {
        /** @var EntityRepository<EmployeeEntity> $repository */
        $repository = $this->container->get('b2b_employee.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));

        /** @var EmployeeEntity|null $employee */
        $employee = $repository->search($criteria, Context::createCLIContext())->first();

        if (! $employee) {
            throw new \RuntimeException(sprintf('Employee with email "%s" not found', $email));
        }

        // In testing, we skip password verification
        // In production, password would be verified here

        // Delegate context creation to EmployeeContextHelper
        return $this->contextHelper->createContextFromEmployee($employee, $salesChannelId);
    }

    /**
     * Quick login without password verification (test-only).
     */
    public function loginByEmail(string $email, ?string $salesChannelId = null): SalesChannelContext
    {
        return $this->login($email, '', $salesChannelId);
    }

    /**
     * Login by employee ID (test-only).
     */
    public function loginById(string $employeeId, ?string $salesChannelId = null): SalesChannelContext
    {
        return $this->contextHelper->createContextForEmployee($employeeId, $salesChannelId);
    }
}
