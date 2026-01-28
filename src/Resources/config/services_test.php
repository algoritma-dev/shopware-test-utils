<?php

declare(strict_types=1);

use Algoritma\ShopwareTestUtils\Core\FactoryRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load('Algoritma\ShopwareTestUtils\Factory\\', __DIR__ . '/../../Factory')
        ->autowire()
        ->autoconfigure()
        ->public()
        ->exclude(__DIR__ . '/../../Factory/AbstractFactory.php')
        ->tag(FactoryRegistry::TAG);
};
