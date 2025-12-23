<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Fixture;

use Algoritma\ShopwareTestUtils\Fixture\AbstractFixture;
use Algoritma\ShopwareTestUtils\Fixture\FixtureManager;
use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FixtureManagerTest extends TestCase
{
    public function testItInjectsContainerIntoFixtures(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $fixture = new class() extends AbstractFixture {
            public bool $containerInjected = false;

            public function load(ReferenceRepository $references): void
            {
                if ($this->getContainer() instanceof ContainerInterface) {
                    $this->containerInjected = true;
                }
            }
        };

        $manager = new FixtureManager($container);
        $manager->load($fixture);

        $this->assertTrue($fixture->containerInjected, 'Container should be injected into the fixture');
    }

    public function testItInjectsContainerIntoDependencies(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $dependency = new class() extends AbstractFixture {
            public static bool $containerInjected = false;

            public function load(ReferenceRepository $references): void
            {
                if ($this->getContainer() instanceof ContainerInterface) {
                    self::$containerInjected = true;
                }
            }
        };

        $mainFixture = new class($dependency::class) extends AbstractFixture {
            public function __construct(private readonly string $dependencyClass) {}

            public function load(ReferenceRepository $references): void {}

            public function getDependencies(): array
            {
                return [$this->dependencyClass];
            }
        };

        $manager = new FixtureManager($container);
        $manager->load($mainFixture);

        $this->assertTrue($dependency::$containerInjected, 'Container should be injected into the dependency fixture');
    }
}
