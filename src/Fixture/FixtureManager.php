<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Fixture;

class FixtureManager
{
    private readonly ReferenceRepository $references;

    /**
     * @var array<class-string<FixtureInterface>, FixtureInterface>
     */
    private array $loadedFixtures = [];

    public function __construct()
    {
        $this->references = new ReferenceRepository();
    }

    /**
     * Load one or more fixtures, resolving dependencies automatically.
     *
     * @param FixtureInterface|array<FixtureInterface> $fixtures
     */
    public function load(FixtureInterface|array $fixtures): void
    {
        if (! is_array($fixtures)) {
            $fixtures = [$fixtures];
        }

        $orderedFixtures = $this->resolveFixtureOrder($fixtures);

        foreach ($orderedFixtures as $fixture) {
            $fixtureClass = $fixture::class;

            if (isset($this->loadedFixtures[$fixtureClass])) {
                continue;
            }

            $fixture->load($this->references);
            $this->loadedFixtures[$fixtureClass] = $fixture;
        }
    }

    public function getReferences(): ReferenceRepository
    {
        return $this->references;
    }

    public function clear(): void
    {
        $this->loadedFixtures = [];
        $this->references->clear();
    }

    /**
     * Resolve fixture loading order based on dependencies.
     *
     * @param array<FixtureInterface> $fixtures
     *
     * @return array<FixtureInterface>
     */
    private function resolveFixtureOrder(array $fixtures): array
    {
        $sorted = [];
        $visiting = [];
        $visited = [];

        foreach ($fixtures as $fixture) {
            $this->topologicalSort($fixture, $sorted, $visiting, $visited);
        }

        return $sorted;
    }

    /**
     * @param array<FixtureInterface> $sorted
     * @param array<string, bool> $visiting
     * @param array<string, bool> $visited
     */
    private function topologicalSort(
        FixtureInterface $fixture,
        array &$sorted,
        array &$visiting,
        array &$visited
    ): void {
        $fixtureClass = $fixture::class;

        if (isset($visited[$fixtureClass])) {
            return;
        }

        if (isset($visiting[$fixtureClass])) {
            throw new \RuntimeException(sprintf('Circular dependency detected for fixture "%s"', $fixtureClass));
        }

        $visiting[$fixtureClass] = true;

        foreach ($fixture->getDependencies() as $dependencyClass) {
            $dependency = new $dependencyClass();

            if (! $dependency instanceof FixtureInterface) {
                throw new \RuntimeException(sprintf('Dependency "%s" must implement FixtureInterface', $dependencyClass));
            }

            $this->topologicalSort($dependency, $sorted, $visiting, $visited);
        }

        unset($visiting[$fixtureClass]);
        $visited[$fixtureClass] = true;
        $sorted[] = $fixture;
    }
}
