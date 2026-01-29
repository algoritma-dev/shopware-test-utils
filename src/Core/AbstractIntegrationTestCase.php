<?php

namespace Algoritma\ShopwareTestUtils\Core;

use Algoritma\ShopwareTestUtils\Factory\CartFactory;
use Algoritma\ShopwareTestUtils\Fixture\FixtureInterface;
use Algoritma\ShopwareTestUtils\Fixture\FixtureManager;
use Algoritma\ShopwareTestUtils\Helper\OrderHelper;
use Algoritma\ShopwareTestUtils\Traits\B2B\B2BHelpersTrait;
use Algoritma\ShopwareTestUtils\Traits\CacheTrait;
use Algoritma\ShopwareTestUtils\Traits\CartTrait;
use Algoritma\ShopwareTestUtils\Traits\CheckoutTrait;
use Algoritma\ShopwareTestUtils\Traits\ContextTrait;
use Algoritma\ShopwareTestUtils\Traits\CustomerTrait;
use Algoritma\ShopwareTestUtils\Traits\EventTrait;
use Algoritma\ShopwareTestUtils\Traits\MailTrait;
use Algoritma\ShopwareTestUtils\Traits\OrderTrait;
use Algoritma\ShopwareTestUtils\Traits\QueueTrait;
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
    use EventTrait;
    use MailTrait;
    use QueueTrait;
    use ContextTrait;
    use CartTrait;
    use CheckoutTrait;
    use OrderTrait;
    use CustomerTrait;
    use B2BHelpersTrait;
    use CacheTrait;

    private ?FixtureManager $fixtureManager = null;

    protected function tearDown(): void
    {
        if ($this->fixtureManager instanceof FixtureManager) {
            $this->fixtureManager->clear();
            $this->fixtureManager = null;
        }

        parent::tearDown();
    }

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
        $this->getFixtureManager()->load($fixtures);
    }

    /**
     * Get a reference to an entity created by a fixture.
     */
    protected function getReference(string $name): mixed
    {
        return $this->getFixtureManager()->getReferences()->get($name);
    }

    /**
     * Check if a reference exists.
     */
    protected function hasReference(string $name): bool
    {
        return $this->getFixtureManager()->getReferences()->has($name);
    }

    private function getFixtureManager(): FixtureManager
    {
        if (! $this->fixtureManager instanceof FixtureManager) {
            $this->fixtureManager = new FixtureManager($this->getContainer());
        }

        return $this->fixtureManager;
    }
}
