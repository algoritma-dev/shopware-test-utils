<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AbstractFactoryTest extends TestCase
{
    private MockObject $container;

    private TestableFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new TestableFactory($this->container);
    }

    public function testWithMethodSetsProperty(): void
    {
        $this->factory->withName('Test Name');

        $data = $this->factory->getData();

        $this->assertArrayHasKey('name', $data);
        $this->assertSame('Test Name', $data['name']);
    }

    public function testSetMethodSetsProperty(): void
    {
        $this->factory->setName('Test Name');

        $data = $this->factory->getData();

        $this->assertArrayHasKey('name', $data);
        $this->assertSame('Test Name', $data['name']);
    }

    public function testWithMethodAppendsIdSuffixForUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $this->factory->withCustomer($uuid);

        $data = $this->factory->getData();

        $this->assertArrayHasKey('customerId', $data);
        $this->assertSame($uuid, $data['customerId']);
    }

    public function testWithMethodDoesNotAppendIdSuffixForNonUuid(): void
    {
        $this->factory->withCustomerNumber('CUST-123');

        $data = $this->factory->getData();

        $this->assertArrayHasKey('customerNumber', $data);
        $this->assertSame('CUST-123', $data['customerNumber']);
    }

    public function testWithMethodDoesNotAppendIdSuffixWhenPropertyEndsWithId(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $this->factory->withCustomerId($uuid);

        $data = $this->factory->getData();

        $this->assertArrayHasKey('customerId', $data);
        $this->assertSame($uuid, $data['customerId']);
        $this->assertArrayNotHasKey('customerIdId', $data);
    }

    public function testWithMethodAppendsIdSuffixForUuidWithoutDashes(): void
    {
        $uuid = '550e8400e29b41d4a716446655440000';
        $this->factory->withProduct($uuid);

        $data = $this->factory->getData();

        $this->assertArrayHasKey('productId', $data);
        $this->assertSame($uuid, $data['productId']);
    }

    public function testFluentInterface(): void
    {
        $result = $this->factory
            ->withName('Test')
            ->withActive(true)
            ->setDescription('Description');

        $this->assertSame($this->factory, $result);
        $data = $this->factory->getData();
        $this->assertSame('Test', $data['name']);
        $this->assertTrue($data['active']);
        $this->assertSame('Description', $data['description']);
    }

    public function testInvalidMethodThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method');

        $this->factory->invalidMethod();
    }

    public function testGetDataReturnsArray(): void
    {
        $this->factory->withName('Test');

        $data = $this->factory->getData();

        $this->assertIsArray($data);
        $this->assertSame(['name' => 'Test'], $data);
    }

    public function testCreatePersistsEntity(): void
    {
        $entity = $this->createMock(Entity::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);

        $repository->expects($this->once())
            ->method('create')
            ->with($this->callback(fn ($data): bool => is_array($data) && isset($data[0]['id'])), $this->isInstanceOf(Context::class));

        $searchResult->method('first')->willReturn($entity);
        $repository->method('search')->willReturn($searchResult);

        $this->container->method('get')
            ->with('test.repository')
            ->willReturn($repository);

        $this->factory->withId('550e8400-e29b-41d4-a716-446655440000');
        $result = $this->factory->create();

        $this->assertSame($entity, $result);
    }

    public function testCreateUsesProvidedContext(): void
    {
        $entity = $this->createMock(Entity::class);
        $repository = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $context = Context::createDefaultContext();

        $repository->expects($this->once())
            ->method('create')
            ->with($this->anything(), $context);

        $searchResult->method('first')->willReturn($entity);
        $repository->method('search')->willReturn($searchResult);

        $this->container->method('get')
            ->with('test.repository')
            ->willReturn($repository);

        $this->factory->withId('550e8400-e29b-41d4-a716-446655440000');
        $this->factory->create($context);
    }

    public function testWithMethodHandlesNullValue(): void
    {
        $this->factory->withName(null);

        $data = $this->factory->getData();

        $this->assertArrayHasKey('name', $data);
        $this->assertNull($data['name']);
    }

    public function testSetMethodAppendsIdSuffixForUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $this->factory->setCategory($uuid);

        $data = $this->factory->getData();

        $this->assertArrayHasKey('categoryId', $data);
        $this->assertSame($uuid, $data['categoryId']);
    }
}

class TestableFactory extends AbstractFactory
{
    protected function getRepositoryName(): string
    {
        return 'test.repository';
    }

    protected function getEntityName(): string
    {
        return 'test_entity';
    }
}
