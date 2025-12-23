<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\ShoppingListFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\ShoppingList\Entity\ShoppingList\ShoppingListEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShoppingListFactoryTest extends TestCase
{
    public function testCreateShoppingList(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $list = new ShoppingListEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($list);

        $factory = new ShoppingListFactory($container);
        $result = $factory->create(Context::createDefaultContext());

        $this->assertInstanceOf(ShoppingListEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new ShoppingListFactory($container);

        $factory->withName('Test List');

        $this->assertInstanceOf(ShoppingListFactory::class, $factory);
    }
}
