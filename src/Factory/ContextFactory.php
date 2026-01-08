<?php

namespace Algoritma\ShopwareTestUtils\Factory;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContextFactory
{
    public function __construct(private readonly ContainerInterface $container) {}

    public function createDefaultContext(): Context
    {
        return Context::createCLIContext();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createSalesChannelContext(?string $salesChannelId = null, array $options = []): SalesChannelContext
    {
        if (! $salesChannelId) {
            $salesChannelId = $this->getSalesChannelId();
        }

        /** @var SalesChannelContextFactory $factory */
        $factory = $this->container->get(SalesChannelContextFactory::class);

        return $factory->create(Uuid::randomHex(), $salesChannelId, $options);
    }

    private function getSalesChannelId(): string
    {
        // Logic to fetch an existing SalesChannel ID or create a default one
        // For simplicity, assuming a default one exists or fetching the first one
        $connection = $this->container->get(Connection::class);
        $id = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM sales_channel LIMIT 1');

        if (! $id) {
            throw new \RuntimeException('No SalesChannel found in database.');
        }

        return $id;
    }
}
