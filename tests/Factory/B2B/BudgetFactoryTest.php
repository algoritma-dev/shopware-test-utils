<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\BudgetFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\BudgetManagement\Entity\Budget\BudgetEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BudgetFactoryTest extends TestCase
{
    public function testCreateBudget(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $budget = new BudgetEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($budget);

        $factory = new BudgetFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(BudgetEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new BudgetFactory($container);

        $factory->withName('Test Budget');

        $this->assertInstanceOf(BudgetFactory::class, $factory);
    }
}
