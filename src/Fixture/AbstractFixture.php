<?php

namespace Algoritma\ShopwareTestUtils\Fixture;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractFixture implements FixtureInterface
{
    protected ContainerInterface $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    abstract public function load(ReferenceRepository $references): void;

    public function getDependencies(): array
    {
        return [];
    }
}
