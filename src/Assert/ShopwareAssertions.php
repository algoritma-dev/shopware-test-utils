<?php

namespace Algoritma\ShopwareTestUtils\Assert;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Assert;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait ShopwareAssertions
{
    use KernelTestBehaviour;

    protected function assertEntityExists(string $entityName, string $id): void
    {
        $repository = $this->getContainer()->get($entityName . '.repository');
        $context = Context::createDefaultContext();

        $result = $repository->search(new Criteria([$id]), $context);

        Assert::assertTrue($result->getTotal() > 0, sprintf('Entity %s with ID %s does not exist.', $entityName, $id));
    }

    protected function assertCustomerHasRole(CustomerEntity $customer, string $role): void
    {
        // Implementation depends on how roles are stored/checked in the specific project context.
        // Assuming a simple check for now or placeholder.
        // Shopware default customers don't have "roles" in the ACL sense directly on the entity usually,
        // but let's assume we check against some custom logic or group.
        // For standard Shopware, maybe checking customer group?
        // Let's leave it as a placeholder or check customer group name.

        // Example: Check if customer is in a group named $role
        $group = $customer->getGroup();
        Assert::assertNotNull($group, 'Customer has no group assigned.');
        Assert::assertEquals($role, $group->getName(), sprintf('Customer is not in group/role %s', $role));
    }

    protected function assertPriceEquals(float $expected, ProductEntity $product): void
    {
        $price = $product->getCurrencyPrice(Defaults::CURRENCY);
        Assert::assertNotNull($price, 'Product has no price for default currency.');
        Assert::assertEquals($expected, $price->getGross(), 'Product price does not match expected value.');
    }

    protected function assertRuleMatches(string $ruleId, SalesChannelContext $context): void
    {
        $ruleIds = $context->getRuleIds();
        Assert::assertContains($ruleId, $ruleIds, sprintf('Rule %s is not active in the current context.', $ruleId));
    }

    // --- Database Assertions ---

    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $qb = $connection->createQueryBuilder();
        $qb->select('COUNT(*)')
            ->from($table);

        foreach ($conditions as $column => $value) {
            $qb->andWhere($qb->expr()->eq($column, $qb->createNamedParameter($value)));
        }

        $count = (int) $qb->executeQuery()->fetchOne();
        Assert::assertGreaterThan(0, $count, sprintf('Failed asserting that table "%s" contains row with conditions: %s', $table, json_encode($conditions)));
    }

    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $qb = $connection->createQueryBuilder();
        $qb->select('COUNT(*)')
            ->from($table);

        foreach ($conditions as $column => $value) {
            $qb->andWhere($qb->expr()->eq($column, $qb->createNamedParameter($value)));
        }

        $count = (int) $qb->executeQuery()->fetchOne();
        Assert::assertEquals(0, $count, sprintf('Failed asserting that table "%s" does not contain row with conditions: %s', $table, json_encode($conditions)));
    }

    protected function assertTableExists(string $table): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $schemaManager = $connection->createSchemaManager();
        Assert::assertTrue($schemaManager->tablesExist([$table]), sprintf('Table "%s" does not exist.', $table));
    }

    protected function assertTableNotExists(string $table): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $schemaManager = $connection->createSchemaManager();
        Assert::assertFalse($schemaManager->tablesExist([$table]), sprintf('Table "%s" exists but should not.', $table));
    }

    protected function assertColumnExists(string $table, string $column): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $schemaManager = $connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns($table);
        Assert::assertArrayHasKey($column, $columns, sprintf('Column "%s" does not exist in table "%s".', $column, $table));
    }

    protected function assertColumnType(string $table, string $column, string $expectedType): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $schemaManager = $connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns($table);
        Assert::assertArrayHasKey($column, $columns, sprintf('Column "%s" does not exist in table "%s".', $column, $table));

        $actualType = $columns[$column]->getType()->getName();
        Assert::assertEquals($expectedType, $actualType, sprintf('Column "%s.%s" has type "%s", expected "%s".', $table, $column, $actualType, $expectedType));
    }

    protected function assertIndexExists(string $table, string $index): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $schemaManager = $connection->createSchemaManager();
        $indexes = $schemaManager->listTableIndexes($table);
        Assert::assertArrayHasKey($index, $indexes, sprintf('Index "%s" does not exist on table "%s".', $index, $table));
    }

    protected function assertForeignKeyExists(string $table, string $foreignKey): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $schemaManager = $connection->createSchemaManager();
        $foreignKeys = $schemaManager->listTableForeignKeys($table);

        $found = false;
        foreach ($foreignKeys as $fk) {
            if ($fk->getName() === $foreignKey) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue($found, sprintf('Foreign key "%s" does not exist on table "%s".', $foreignKey, $table));
    }

    protected function assertRowCount(string $table, int $expectedCount): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $count = (int) $connection->fetchOne("SELECT COUNT(*) FROM `{$table}`");
        Assert::assertEquals($expectedCount, $count, sprintf('Table "%s" has %d rows, expected %d.', $table, $count, $expectedCount));
    }

    protected function assertEntityNotExists(string $entityName, string $id): void
    {
        $repository = $this->getContainer()->get($entityName . '.repository');
        $context = Context::createDefaultContext();

        $result = $repository->search(new Criteria([$id]), $context);

        Assert::assertEquals(0, $result->getTotal(), sprintf('Entity %s with ID %s exists but should not.', $entityName, $id));
    }

    protected function assertEntityCount(string $entityName, int $expectedCount, ?Criteria $criteria = null): void
    {
        $repository = $this->getContainer()->get($entityName . '.repository');
        $context = Context::createDefaultContext();

        if (! $criteria instanceof Criteria) {
            $criteria = new Criteria();
        }

        $result = $repository->search($criteria, $context);
        Assert::assertEquals($expectedCount, $result->getTotal(), sprintf('Entity "%s" has %d records, expected %d.', $entityName, $result->getTotal(), $expectedCount));
    }

    protected function assertEntityHasAttribute(string $entityName, string $id, string $attribute, $expectedValue): void
    {
        $repository = $this->getContainer()->get($entityName . '.repository');
        $context = Context::createDefaultContext();

        $entity = $repository->search(new Criteria([$id]), $context)->first();
        Assert::assertNotNull($entity, sprintf('Entity %s with ID %s does not exist.', $entityName, $id));

        $getter = 'get' . ucfirst($attribute);
        if (! method_exists($entity, $getter)) {
            Assert::fail(sprintf('Entity %s does not have attribute/getter "%s".', $entityName, $attribute));
        }

        $actualValue = $entity->$getter();
        Assert::assertEquals($expectedValue, $actualValue, sprintf('Entity %s attribute "%s" has value "%s", expected "%s".', $entityName, $attribute, $actualValue, $expectedValue));
    }

    // --- Cart/Order Assertions ---

    protected function assertCartContainsProduct(Cart $cart, string $productId): void
    {
        $lineItems = $cart->getLineItems();
        $found = false;

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue($found, sprintf('Cart does not contain product with ID %s', $productId));
    }

    protected function assertCartTotal(Cart $cart, float $expectedTotal): void
    {
        $actualTotal = $cart->getPrice()->getTotalPrice();
        Assert::assertEquals($expectedTotal, $actualTotal, sprintf('Cart total is %.2f, expected %.2f', $actualTotal, $expectedTotal));
    }

    protected function assertCartItemQuantity(Cart $cart, string $productId, int $expectedQuantity): void
    {
        $lineItems = $cart->getLineItems();
        $quantity = 0;

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === $productId) {
                $quantity = $lineItem->getQuantity();
                break;
            }
        }

        Assert::assertEquals($expectedQuantity, $quantity, sprintf('Product %s has quantity %d, expected %d', $productId, $quantity, $expectedQuantity));
    }

    protected function assertOrderState(OrderEntity $order, string $expectedState): void
    {
        $stateName = $order->getStateMachineState()?->getTechnicalName();
        Assert::assertEquals($expectedState, $stateName, sprintf('Order state is "%s", expected "%s"', $stateName, $expectedState));
    }

    protected function assertOrderHasTransaction(OrderEntity $order): void
    {
        $transactions = $order->getTransactions();
        Assert::assertNotNull($transactions, 'Order has no transactions collection');
        Assert::assertGreaterThan(0, $transactions->count(), 'Order has no transactions');
    }

    protected function assertOrderHasDelivery(OrderEntity $order): void
    {
        $deliveries = $order->getDeliveries();
        Assert::assertNotNull($deliveries, 'Order has no deliveries collection');
        Assert::assertGreaterThan(0, $deliveries->count(), 'Order has no deliveries');
    }

    protected function assertLineItemPrice(OrderEntity $order, string $lineItemId, float $expectedPrice): void
    {
        $lineItems = $order->getLineItems();
        Assert::assertNotNull($lineItems, 'Order has no line items');

        $lineItem = $lineItems->get($lineItemId);
        Assert::assertNotNull($lineItem, sprintf('Line item %s not found in order', $lineItemId));

        $actualPrice = $lineItem->getPrice()->getTotalPrice();
        Assert::assertEquals($expectedPrice, $actualPrice, sprintf('Line item %s has price %.2f, expected %.2f', $lineItemId, $actualPrice, $expectedPrice));
    }

    // --- Product Assertions ---

    protected function assertProductInStock(ProductEntity $product, int $minStock = 1): void
    {
        $stock = $product->getStock();
        Assert::assertGreaterThanOrEqual($minStock, $stock, sprintf('Product has stock %d, expected at least %d', $stock, $minStock));
    }

    protected function assertProductOutOfStock(ProductEntity $product): void
    {
        $stock = $product->getStock();
        Assert::assertEquals(0, $stock, sprintf('Product has stock %d, expected 0', $stock));
    }

    protected function assertProductActive(ProductEntity $product): void
    {
        Assert::assertTrue($product->getActive(), 'Product is not active');
    }

    protected function assertProductInactive(ProductEntity $product): void
    {
        Assert::assertFalse($product->getActive(), 'Product is active but should be inactive');
    }

    protected function assertProductHasCategory(ProductEntity $product, string $categoryId): void
    {
        $categories = $product->getCategoryTree();
        Assert::assertNotNull($categories, 'Product has no category tree');
        Assert::assertContains($categoryId, $categories, sprintf('Product does not have category %s', $categoryId));
    }

    protected function assertPriceInRange(ProductEntity $product, float $min, float $max): void
    {
        $price = $product->getCurrencyPrice(Defaults::CURRENCY);
        Assert::assertNotNull($price, 'Product has no price for default currency');

        $gross = $price->getGross();
        Assert::assertGreaterThanOrEqual($min, $gross, sprintf('Price %.2f is below minimum %.2f', $gross, $min));
        Assert::assertLessThanOrEqual($max, $gross, sprintf('Price %.2f is above maximum %.2f', $gross, $max));
    }

    // --- Customer/Auth Assertions ---

    protected function assertCustomerLoggedIn(SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();
        Assert::assertNotNull($customer, 'No customer logged in (context has no customer)');
    }

    protected function assertGuestSession(SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();
        Assert::assertNull($customer, 'Customer is logged in but should be guest');
    }

    protected function assertCustomerHasAddress(CustomerEntity $customer, string $addressId): void
    {
        $addresses = $customer->getAddresses();
        Assert::assertNotNull($addresses, 'Customer has no addresses');

        $found = false;
        foreach ($addresses as $address) {
            if ($address->getId() === $addressId) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue($found, sprintf('Customer does not have address %s', $addressId));
    }

    protected function assertCustomerBelongsToGroup(CustomerEntity $customer, string $groupId): void
    {
        $group = $customer->getGroup();
        Assert::assertNotNull($group, 'Customer has no group');
        Assert::assertEquals($groupId, $group->getId(), sprintf('Customer is in group %s, expected %s', $group->getId(), $groupId));
    }

    // --- Sales Channel Assertions ---

    protected function assertSalesChannelActive(string $salesChannelId): void
    {
        $repository = $this->getContainer()->get('sales_channel.repository');
        $context = Context::createDefaultContext();

        $salesChannel = $repository->search(new Criteria([$salesChannelId]), $context)->first();
        Assert::assertNotNull($salesChannel, sprintf('Sales channel %s not found', $salesChannelId));
        Assert::assertTrue($salesChannel->getActive(), sprintf('Sales channel %s is not active', $salesChannelId));
    }

    protected function assertContextCurrency(SalesChannelContext $context, string $currencyId): void
    {
        $actualCurrencyId = $context->getCurrency()->getId();
        Assert::assertEquals($currencyId, $actualCurrencyId, sprintf('Context currency is %s, expected %s', $actualCurrencyId, $currencyId));
    }

    protected function assertContextLanguage(SalesChannelContext $context, string $languageId): void
    {
        $actualLanguageId = $context->getLanguageId();
        Assert::assertEquals($languageId, $actualLanguageId, sprintf('Context language is %s, expected %s', $actualLanguageId, $languageId));
    }

    // --- State Machine Assertions ---

    protected function assertStateMachineState(string $entityId, string $expectedState, string $stateMachineName = 'order.state'): void
    {
        $connection = $this->getContainer()->get(Connection::class);

        $sql = <<<'EOD'

                        SELECT state_machine_state.technical_name
                        FROM state_machine_state
                        INNER JOIN state_machine_history ON state_machine_history.to_state_id = state_machine_state.id
                        WHERE state_machine_history.entity_id = UNHEX(:entityId)
                        ORDER BY state_machine_history.created_at DESC
                        LIMIT 1
                    
            EOD;

        $stateName = $connection->fetchOne($sql, ['entityId' => str_replace('-', '', $entityId)]);
        Assert::assertEquals($expectedState, $stateName, sprintf('Entity %s has state "%s", expected "%s"', $entityId, $stateName, $expectedState));
    }
}
