<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Fixture;

use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;
use PHPUnit\Framework\TestCase;

class ReferenceRepositoryTest extends TestCase
{
    private ReferenceRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new ReferenceRepository();
    }

    public function testSetStoresReference(): void
    {
        $object = new \stdClass();
        $this->repository->set('test', $object);

        $this->assertTrue($this->repository->has('test'));
    }

    public function testGetReturnsStoredReference(): void
    {
        $object = new \stdClass();
        $object->value = 'test value';

        $this->repository->set('test', $object);
        $retrieved = $this->repository->get('test');

        $this->assertSame($object, $retrieved);
        $this->assertSame('test value', $retrieved->value);
    }

    public function testGetThrowsExceptionForNonExistentReference(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Reference "nonexistent" does not exist');

        $this->repository->get('nonexistent');
    }

    public function testHasReturnsTrueForExistingReference(): void
    {
        $this->repository->set('test', 'value');

        $this->assertTrue($this->repository->has('test'));
    }

    public function testHasReturnsFalseForNonExistentReference(): void
    {
        $this->assertFalse($this->repository->has('nonexistent'));
    }

    public function testGetAllReturnsAllReferences(): void
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $this->repository->set('ref1', $object1);
        $this->repository->set('ref2', $object2);

        $all = $this->repository->getAll();

        $this->assertCount(2, $all);
        $this->assertSame($object1, $all['ref1']);
        $this->assertSame($object2, $all['ref2']);
    }

    public function testGetAllReturnsEmptyArrayWhenNoReferences(): void
    {
        $all = $this->repository->getAll();

        $this->assertSame([], $all);
    }

    public function testClearRemovesAllReferences(): void
    {
        $this->repository->set('ref1', 'value1');
        $this->repository->set('ref2', 'value2');

        $this->repository->clear();

        $this->assertFalse($this->repository->has('ref1'));
        $this->assertFalse($this->repository->has('ref2'));
        $this->assertSame([], $this->repository->getAll());
    }

    public function testSetOverwritesExistingReference(): void
    {
        $this->repository->set('test', 'old value');
        $this->repository->set('test', 'new value');

        $this->assertSame('new value', $this->repository->get('test'));
    }

    public function testCanStoreNullValue(): void
    {
        $this->repository->set('null_ref', null);

        $this->assertTrue($this->repository->has('null_ref'));
        $this->assertNull($this->repository->get('null_ref'));
    }

    public function testCanStoreArrayValue(): void
    {
        $array = ['key' => 'value', 'nested' => ['data' => 123]];
        $this->repository->set('array_ref', $array);

        $retrieved = $this->repository->get('array_ref');

        $this->assertSame($array, $retrieved);
    }

    public function testCanStoreScalarValues(): void
    {
        $this->repository->set('string', 'test');
        $this->repository->set('int', 42);
        $this->repository->set('float', 3.14);
        $this->repository->set('bool', true);

        $this->assertSame('test', $this->repository->get('string'));
        $this->assertSame(42, $this->repository->get('int'));
        $this->assertSame(3.14, $this->repository->get('float'));
        $this->assertTrue($this->repository->get('bool'));
    }
}
