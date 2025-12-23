<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\EmployeeFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmployeeFactoryTest extends TestCase
{
    public function testCreateEmployee(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $employee = new EmployeeEntity();

        $container->method('get')->willReturn($repository);
        $repository->method('search')->willReturn($searchResult);
        $searchResult->method('first')->willReturn($employee);

        $factory = new EmployeeFactory($container);
        $result = $factory->create(Context::createDefaultContext());

        $this->assertInstanceOf(EmployeeEntity::class, $result);
    }

    public function testWithName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new EmployeeFactory($container);

        $factory->withName('John', 'Doe');

        $this->assertInstanceOf(EmployeeFactory::class, $factory);
    }
}
