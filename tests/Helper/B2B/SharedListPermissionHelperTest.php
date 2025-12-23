<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper\B2B;

use Algoritma\ShopwareTestUtils\Helper\B2B\SharedListPermissionHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SharedListPermissionHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(ShoppingListEntity::class)) {
            $this->markTestSkipped('Shopware Commercial B2B extension is not installed.');
        }
    }

    public function testCanAccess(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $shoppingList = new ShoppingListEntity();

        $shoppingList->setEmployeeId('employee-id');

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($shoppingList);

        $helper = new SharedListPermissionHelper($container);
        $result = $helper->canAccess('employee-id', 'list-id');

        $this->assertTrue($result);
    }

    public function testShareWith(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);

        $container->method('get')->willReturn($repository);
        $repository->expects($this->once())->method('update');

        $helper = new SharedListPermissionHelper($container);
        $helper->shareWith('list-id', 'employee-id');
    }
}
