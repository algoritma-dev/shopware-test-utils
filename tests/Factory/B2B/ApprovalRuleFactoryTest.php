<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\ApprovalRuleFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\OrderApproval\Entity\ApprovalRule\ApprovalRuleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApprovalRuleFactoryTest extends TestCase
{
    public function testCreateApprovalRule(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $searchResult = $this->createStub(EntitySearchResult::class);
        $rule = new ApprovalRuleEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($rule);

        $factory = new ApprovalRuleFactory($container);
        $result = $factory->create(Context::createCLIContext());

        $this->assertInstanceOf(ApprovalRuleEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new ApprovalRuleFactory($container);

        $factory->withName('Test Rule');

        $this->assertInstanceOf(ApprovalRuleFactory::class, $factory);
    }
}
