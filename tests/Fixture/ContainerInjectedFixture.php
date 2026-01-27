<?php

namespace Algoritma\ShopwareTestUtils\Tests\Fixture;

use Algoritma\ShopwareTestUtils\Fixture\AbstractFixture;
use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerInjectedFixture extends AbstractFixture
{
    public bool $containerInjected = false;

    public function load(ReferenceRepository $references): void
    {
        if ($this->getContainer() instanceof ContainerInterface) {
            $this->containerInjected = true;
        }
    }
}
