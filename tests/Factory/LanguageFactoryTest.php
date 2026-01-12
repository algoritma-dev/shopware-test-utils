<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\LanguageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\Language\LanguageDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LanguageFactoryTest extends TestCase
{
    private MockObject $container;

    private LanguageFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new LanguageFactory($this->container);
    }

    public function testConstructorInitializesDefaults(): void
    {
        $data = $this->factory->getData();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('active', $data);
        $this->assertTrue($data['active']);
        $this->assertIsString($data['id']);
        $this->assertNotEmpty($data['id']);
    }

    public function testGetRepositoryName(): void
    {
        $reflection = new \ReflectionClass($this->factory);
        $method = $reflection->getMethod('getRepositoryName');

        $result = $method->invoke($this->factory);

        $this->assertSame('language.repository', $result);
    }

    public function testGetEntityName(): void
    {
        $reflection = new \ReflectionClass($this->factory);
        $method = $reflection->getMethod('getEntityName');

        $result = $method->invoke($this->factory);

        $this->assertSame(LanguageDefinition::ENTITY_NAME, $result);
    }

    public function testFluentInterface(): void
    {
        $result = $this->factory->withName('German')->setActive(false);

        $this->assertSame($this->factory, $result);
        $data = $this->factory->getData();
        $this->assertSame('German', $data['name']);
        $this->assertFalse($data['active']);
    }

    public function testGeneratesUniqueIdForEachInstance(): void
    {
        $factory1 = new LanguageFactory($this->container);
        $factory2 = new LanguageFactory($this->container);

        $id1 = $factory1->getData()['id'];
        $id2 = $factory2->getData()['id'];

        $this->assertNotSame($id1, $id2);
    }
}
