<?php

declare(strict_types=1);

use Algoritma\ShopwareTestUtils\Core\FactoryRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(FactoryRegistry::class)
        ->public();
};
