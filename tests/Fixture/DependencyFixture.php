<?php

namespace Algoritma\ShopwareTestUtils\Tests\Fixture;

use Algoritma\ShopwareTestUtils\Fixture\AbstractFixture;
use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DependencyFixture extends AbstractFixture
{
    public static bool $containerInjected = false;

    public function load(ReferenceRepository $references): void
    {
        if ($this->getContainer() instanceof ContainerInterface) {
            self::$containerInjected = true;
        }
    }
}
