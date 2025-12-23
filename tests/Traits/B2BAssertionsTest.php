<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Traits\B2BAssertions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Permission\PermissionCollection;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Permission\PermissionEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Role\RoleEntity;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteLineItem\QuoteLineItemCollection;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteLineItem\QuoteLineItemEntity;
use Shopware\Commercial\B2B\QuoteManagement\Entity\Quote\QuoteStates;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListEntity;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingListShared\ShoppingListSharedCollection;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingListShared\ShoppingListSharedEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class B2BAssertionsTest extends TestCase
{
    use B2BAssertions;

    private MockObject $container;

    protected function setUp(): void
    {
        if (! class_exists(BudgetEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testAssertQuoteInState(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $quote = new QuoteEntity();
        $state = new StateMachineStateEntity();

        $state->setTechnicalName('open');
        $quote->setStateMachineState($state);

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($quote);

        $this->assertQuoteInState('quote-id', 'open');
    }

    public function testAssertBudgetExceeded(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $budget = new BudgetEntity();

        $budget->setAmount(100.0);
        $budget->setUsedAmount(110.0);

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $this->assertBudgetExceeded('budget-id');
    }

    public function testAssertEmployeeHasPermission(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $employee = new EmployeeEntity();
        $role = new RoleEntity();
        $permission = new PermissionEntity();

        $permission->setCode('test.permission');
        $role->setPermissions(new PermissionCollection([$permission]));
        $employee->setRole($role);

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($employee);

        $this->assertEmployeeHasPermission('employee-id', 'test.permission');
    }

    public function testAssertOrderNeedsApproval(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $pendingOrder = new PendingOrderEntity();

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($pendingOrder);

        $this->assertOrderNeedsApproval('order-id');
    }

    public function testAssertPendingOrderCreated(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $pendingOrder = new PendingOrderEntity();

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('getElements')->willReturn([$pendingOrder]);

        $this->assertPendingOrderCreated('employee-id');
    }

    public function testAssertPendingOrderInState(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $pendingOrder = new PendingOrderEntity();
        $state = new StateMachineStateEntity();

        $state->setTechnicalName('pending');
        $pendingOrder->setStateMachineState($state);

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($pendingOrder);

        $this->assertPendingOrderInState('pending-id', 'pending');
    }

    public function testAssertShoppingListShared(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $shoppingList = new ShoppingListEntity();
        $shared = new ShoppingListSharedEntity();

        $shared->setEmployeeId('employee-id');
        $shoppingList->setSharedWith(new ShoppingListSharedCollection([$shared]));

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($shoppingList);

        $this->assertShoppingListShared('list-id', 'employee-id');
    }

    public function testAssertEmployeeBelongsToOrganization(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $employee = new EmployeeEntity();

        $employee->setOrganizationId('org-id');

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($employee);

        $this->assertEmployeeBelongsToOrganization('employee-id', 'org-id');
    }

    public function testAssertQuoteHasComments(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('getElements')->willReturn([new \stdClass()]);

        $this->assertQuoteHasComments('quote-id');
    }

    public function testAssertBudgetNotificationTriggered(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $budget = new BudgetEntity();

        $budget->setNotify(true);
        $budget->setAmount(100.0);
        $budget->setUsedAmount(90.0);
        $budget->setNotificationConfig(['type' => 'percentage', 'value' => 80]);

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $this->assertBudgetNotificationTriggered('budget-id');
    }

    public function testAssertEmployeeHasRole(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $employee = new EmployeeEntity();

        $employee->setRoleId('role-id');

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($employee);

        $this->assertEmployeeHasRole('employee-id', 'role-id');
    }

    public function testAssertQuoteCanBeConverted(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $quote = new QuoteEntity();
        $state = new StateMachineStateEntity();

        $state->setTechnicalName(QuoteStates::STATE_ACCEPTED);
        $quote->setStateMachineState($state);
        $quote->setLineItems(new QuoteLineItemCollection([new QuoteLineItemEntity()]));

        $this->container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($quote);

        $this->assertQuoteCanBeConverted('quote-id');
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
