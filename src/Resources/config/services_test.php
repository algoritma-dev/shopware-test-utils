<?php

declare(strict_types=1);

use Algoritma\ShopwareTestUtils\Core\FactoryRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->bind(ContainerInterface::class, service('test.service_container'));

    $services->load('Algoritma\ShopwareTestUtils\Factory\\', __DIR__ . '/../../Factory')
        ->autowire()
        ->autoconfigure()
        ->public()
        ->exclude(__DIR__ . '/../../Factory/AbstractFactory.php')
        ->tag(FactoryRegistry::TAG);
};
