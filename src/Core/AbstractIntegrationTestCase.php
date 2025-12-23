<?php

namespace Algoritma\ShopwareTestUtils\Core;

use Algoritma\ShopwareTestUtils\Factory\CartFactory;
use Algoritma\ShopwareTestUtils\Fixture\FixtureInterface;
use Algoritma\ShopwareTestUtils\Fixture\FixtureManager;
use Algoritma\ShopwareTestUtils\Helper\OrderHelper;
use Algoritma\ShopwareTestUtils\Traits\EventHelpers;
use Algoritma\ShopwareTestUtils\Traits\MailHelpers;
use Algoritma\ShopwareTestUtils\Traits\QueueHelpers;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\EventDispatcherBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractIntegrationTestCase extends TestCase
{
    use IntegrationTestBehaviour;
    use DatabaseTransactionBehaviour;
    use QueueTestBehaviour;
    use EventDispatcherBehaviour;
    use EventHelpers;
    use MailHelpers;
    use QueueHelpers;

    protected function getConnection(): Connection
    {
        return $this->getContainer()->get(Connection::class);
    }

    /**
     * @return object|null
     */
    protected function getService(string $serviceId)
    {
        return $this->getContainer()->get($serviceId);
    }

    // --- Factories (create entities) ---

    /**
     * Creates a CartFactory to build a cart with fluent interface.
     * Factory = responsible for CREATING carts.
     */
    protected function createCart(SalesChannelContext $context): CartFactory
    {
        return new CartFactory($this->getContainer(), $context);
    }

    // --- Helpers (perform actions on entities) ---

    /**
     * Places an order from a cart.
     * Helper = responsible for ACTIONS on existing entities.
     */
    protected function placeOrder(Cart $cart, SalesChannelContext $context): OrderEntity
    {
        return (new OrderHelper($this->getContainer()))->placeOrder($cart, $context);
    }

    /**
     * Creates a SalesChannelContext with a logged-in customer.
     */
    protected function createAuthenticatedContext(CustomerEntity $customer): SalesChannelContext
    {
        $options = [
            SalesChannelContextService::CUSTOMER_ID => $customer->getId(),
        ];

        /** @var SalesChannelContextFactory $factory */
        $factory = $this->getContainer()->get(SalesChannelContextFactory::class);

        $salesChannelId = $customer->getSalesChannelId();

        return $factory->create(Uuid::randomHex(), $salesChannelId, $options);
    }

    /**
     * Loads one or more fixtures.
     *
     * @param FixtureInterface|array<FixtureInterface> $fixtures
     */
    protected function loadFixtures(FixtureInterface|array $fixtures): void
    {
        $fixtureManager = new FixtureManager($this->getContainer());
        $fixtureManager->load($fixtures);
    }
}
