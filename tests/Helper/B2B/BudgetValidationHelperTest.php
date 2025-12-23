<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\BudgetValidationHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BudgetValidationHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(BudgetEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testExceedsBudget(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $budget = new BudgetEntity();
        $cart = $this->createStub(Cart::class);
        $price = $this->createStub(CartPrice::class);

        $budget->setAmount(100.0);
        $budget->setUsedAmount(50.0);

        $cart->method('getPrice')->willReturn($price);
        $price->method('getTotalPrice')->willReturn(60.0);

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $helper = new BudgetValidationHelper($container);
        $result = $helper->exceedsBudget($cart, 'budget-id');

        $this->assertTrue($result);
    }

    public function testSimulateBudgetUsage(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $budget = new BudgetEntity();

        $budget->setUsedAmount(50.0);

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $repository->expects($this->once())->method('update');

        $helper = new BudgetValidationHelper($container);
        $result = $helper->simulateBudgetUsage('budget-id', 10.0);

        $this->assertSame($budget, $result);
    }
}
