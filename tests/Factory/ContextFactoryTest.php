<?php

namespace Algoritma\ShopwareTestUtils\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\ContextFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContextFactoryTest extends TestCase
{
    public function testCreateDefaultContext(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory = new ContextFactory($container);

        $result = $factory->createDefaultContext();

        $this->assertInstanceOf(Context::class, $result);
    }

    public function testCreateSalesChannelContext(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $scFactory = $this->createStub(SalesChannelContextFactory::class);
        $context = $this->createStub(SalesChannelContext::class);
        $connection = $this->createStub(Connection::class);

        $container->method('get')->willReturnMap([
            [SalesChannelContextFactory::class, 1, $scFactory],
            [Connection::class, 1, $connection],
        ]);

        $scFactory->method('create')->willReturn($context);
        $connection->method('fetchOne')->willReturn('sales-channel-id');

        $factory = new ContextFactory($container);
        $result = $factory->createSalesChannelContext();

        $this->assertSame($context, $result);
    }
}
