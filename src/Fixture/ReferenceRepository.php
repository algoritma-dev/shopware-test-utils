<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Fixture;

class ReferenceRepository
{
    /**
     * @var array<string, mixed>
     */
    private array $references = [];

    public function set(string $name, mixed $object): void
    {
        $this->references[$name] = $object;
    }

    public function get(string $name): mixed
    {
        if (! $this->has($name)) {
            throw new \InvalidArgumentException(sprintf('Reference "%s" does not exist', $name));
        }

        return $this->references[$name];
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->references);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->references;
    }

    public function clear(): void
    {
        $this->references = [];
    }
}
