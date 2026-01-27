<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Fixture;

use Algoritma\ShopwareTestUtils\Fixture\FixtureManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FixtureManagerTest extends TestCase
{
    public function testItInjectsContainerIntoFixtures(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $fixture = new ContainerInjectedFixture();

        $manager = new FixtureManager($container);
        $manager->load($fixture);

        $this->assertTrue($fixture->containerInjected, 'Container should be injected into the fixture');
    }

    public function testItInjectsContainerIntoDependencies(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $mainFixture = new MainFixture(DependencyFixture::class);

        $manager = new FixtureManager($container);
        $manager->load($mainFixture);

        $this->assertTrue(DependencyFixture::$containerInjected, 'Container should be injected into the dependency fixture');
    }
}
