<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\B2B\B2BContextFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory as ShopwareSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class B2BContextFactoryTest extends TestCase
{
    public function testCreateContext(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $scFactory = $this->createMock(ShopwareSalesChannelContextFactory::class);
        $context = $this->createMock(SalesChannelContext::class);
        $connection = $this->createMock(Connection::class);

        $container->method('get')->willReturnMap([
            [ShopwareSalesChannelContextFactory::class, 1, $scFactory],
            [Connection::class, 1, $connection],
        ]);

        $scFactory->method('create')->willReturn($context);
        $connection->method('fetchOne')->willReturn('sales-channel-id');

        $factory = new B2BContextFactory($container);
        $result = $factory->create();

        $this->assertSame($context, $result);
    }

    public function testWithEmployee(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new B2BContextFactory($container);

        $factory->withEmployee('employee-id');

        $this->assertInstanceOf(B2BContextFactory::class, $factory);
    }
}
