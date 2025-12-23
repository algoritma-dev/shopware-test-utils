<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Fixture;

use Symfony\Component\DependencyInjection\ContainerInterface;

interface FixtureInterface
{
    /**
     * Load fixture data.
     */
    public function load(ReferenceRepository $references): void;

    /**
     * Get dependencies - array of fixture class names that must be loaded before this one.
     *
     * @return array<class-string<FixtureInterface>>
     */
    public function getDependencies(): array;

    public function setContainer(ContainerInterface $container): void;
}
