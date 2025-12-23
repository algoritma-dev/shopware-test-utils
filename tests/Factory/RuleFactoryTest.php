<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\RuleFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RuleFactoryTest extends TestCase
{
    public function testCreateRule(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $rule = new RuleEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($rule);

        $factory = new RuleFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(RuleEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new RuleFactory($container);

        $factory->withName('Test Rule');

        $this->assertInstanceOf(RuleFactory::class, $factory);
    }
}
