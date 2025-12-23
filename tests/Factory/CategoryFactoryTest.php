<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\CategoryFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CategoryFactoryTest extends TestCase
{
    public function testCreateCategory(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $category = new CategoryEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($category);

        $factory = new CategoryFactory($container);
        $result = $factory->create(Context::createDefaultContext());

        $this->assertInstanceOf(CategoryEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new CategoryFactory($container);

        $factory->withName('Test Category');

        $this->assertInstanceOf(CategoryFactory::class, $factory);
    }
}
