<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Fixture;

use Algoritma\ShopwareTestUtils\Fixture\AbstractFixture;
use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AbstractFixtureTest extends TestCase
{
    public function testSetAndGetContainer(): void
    {
        $fixture = new class() extends AbstractFixture {
            public function load(ReferenceRepository $references): void {}
        };

        $container = $this->createMock(ContainerInterface::class);
        $fixture->setContainer($container);

        $this->assertSame($container, $fixture->getContainer());
    }
}
