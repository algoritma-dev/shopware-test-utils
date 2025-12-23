<?php

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\StateManager;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StateManagerTest extends TestCase
{
    public function testTransitionOrderState(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $registry = $this->createMock(StateMachineRegistry::class);

        $container->method('get')->willReturn($registry);

        $registry->expects($this->once())->method('transition');

        $manager = new StateManager($container);
        $manager->transitionOrderState('order-id', 'cancel', Context::createDefaultContext());
    }

    public function testTransitionPaymentState(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $registry = $this->createMock(StateMachineRegistry::class);

        $container->method('get')->willReturn($registry);

        $registry->expects($this->once())->method('transition');

        $manager = new StateManager($container);
        $manager->transitionPaymentState('transaction-id', 'pay', Context::createDefaultContext());
    }
}
