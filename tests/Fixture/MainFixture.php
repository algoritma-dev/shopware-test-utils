<?php

namespace Algoritma\ShopwareTestUtils\Tests\Fixture;

use Algoritma\ShopwareTestUtils\Fixture\AbstractFixture;
use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;

class MainFixture extends AbstractFixture
{
    public function __construct(private readonly string $dependencyClass) {}

    public function load(ReferenceRepository $references): void {}

    public function getDependencies(): array
    {
        return [$this->dependencyClass];
    }
}
