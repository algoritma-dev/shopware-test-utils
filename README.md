# Shopware Test Utils

A comprehensive collection of helpers, factories, and utilities for **Shopware 6** integration and functional tests.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue)](https://www.php.net/)
[![Shopware Version](https://img.shields.io/badge/shopware-%5E6.7-blue)](https://www.shopware.com/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

---

## 📦 Installation

```bash
composer require --dev algoritma/shopware-test-utils
```

---

## 🎯 Overview

This library provides a structured, clean, and maintainable approach to writing tests for Shopware 6 projects. It follows strict **Single Responsibility Principle (SRP)** and the **Factory/Helper/Trait** pattern to ensure separation of concerns.

### Core Philosophy

> **"Factory CREATES, Helper ACTS, Trait SHARES"**

- **Factories** → Create and configure entities (Products, Orders, Customers, etc.)
- **Helpers** → Execute actions on existing entities (place order, cancel, transition states)
- **Traits** → Provide reusable behaviors (time travel, event capture, cache management)

---

## 🚀 Quick Start

### Basic Integration Test

```php
<?php

use Algoritma\ShopwareTestUtils\Core\AbstractIntegrationTestCase;
use Algoritma\ShopwareTestUtils\Factory\ProductFactory;
use Algoritma\ShopwareTestUtils\Factory\CustomerFactory;

class OrderPlacementTest extends AbstractIntegrationTestCase
{
    public function testUserCanPlaceOrder(): void
    {
        // Create test entities using Factories
        $customer = (new CustomerFactory($this->getContainer()))
            ->withEmail('test@example.com')
            ->withFirstName('John')
            ->withLastName('Doe')
            ->create();

        $product = (new ProductFactory($this->getContainer()))
            ->withName('Test Product')
            ->withPrice(19.99)
            ->withStock(100)
            ->create();

        $context = $this->createAuthenticatedContext($customer);

        // Create cart and add product
        $cart = $this->createCart($context)
            ->withProduct($product->getId())
            ->create();

        // Place order using Helper
        $order = $this->placeOrder($cart, $context);

        // Assert order was created
        $this->assertOrderState($order, 'open');
        $this->assertDatabaseHas('order', ['id' => $order->getId()]);
    }
}
```

### Functional/Storefront Test

```php
<?php

use Algoritma\ShopwareTestUtils\Core\AbstractFunctionalTestCase;

class StorefrontCheckoutTest extends AbstractFunctionalTestCase
{
    public function testCheckoutFlow(): void
    {
        // Create storefront request helper
        $storefront = $this->createStorefrontHelper();

        // Simulate user actions
        $storefront->addProductToCart($productId);
        $storefront->goToCheckout();
        $response = $storefront->submitOrder();

        $this->assertResponseIsSuccessful($response);
        $this->assertMailSent('order_confirmation');
    }
}
```

---

## 📚 Available Components

### 🏭 Factories (Create Entities)

Factories use the **Builder pattern** to create and configure entities with a fluent API.

| Factory | Description |
|---------|-------------|
| `ProductFactory` | Create products with variants, prices, and stock |
| `CustomerFactory` | Create customers with addresses and groups |
| `OrderFactory` | Create orders with line items and states |
| `CategoryFactory` | Create category trees |
| `CartFactory` | Build carts with products and promotions |
| `MediaFactory` | Create media files (images, documents) |
| `PromotionFactory` | Create promotions and discounts |
| `RuleFactory` | Create business rules |
| `SalesChannelFactory` | Create sales channels |
| `ShippingMethodFactory` | Create shipping methods |
| `PaymentMethodFactory` | Create payment methods |
| `TaxFactory` | Create tax configurations |

**Example:**

```php
$product = (new ProductFactory($container))
    ->withName('Gaming Laptop')
    ->withPrice(1499.99)
    ->withStock(50)
    ->withTax(19.0)
    ->active()
    ->create();
```

### 🔧 Helpers (Execute Actions)

Helpers perform operations on existing entities.

| Helper | Description |
|--------|-------------|
| `OrderHelper` | Cancel orders, mark as paid/shipped, get order details |
| `CartHelper` | Clear cart, remove items, recalculate |
| `MediaHelper` | Assign media to products, delete media |
| `StateManager` | Transition state machine states |
| `CheckoutRunner` | Execute complete checkout flows |
| `StorefrontRequestHelper` | Simulate storefront HTTP requests |
| `MigrationDataTester` | Test data integrity in migrations |

**Example:**

```php
$orderHelper = new OrderHelper($container);
$orderHelper->markOrderAsPaid($orderId);
$orderHelper->markOrderAsShipped($orderId);
$orderHelper->cancelOrder($orderId);
```

### ✨ Traits (Reusable Behaviors)

Traits provide reusable methods that can be mixed into test cases.

| Trait | Description |
|-------|-------------|
| `DatabaseHelpers` | Truncate tables, seed data, snapshots, transactions |
| `CacheHelpers` | Clear cache, invalidate tags |
| `TimeHelpers` | Freeze time, travel to specific dates |
| `ConfigHelpers` | Get/set system configuration |
| `LogHelpers` | Capture and assert log entries |
| `MailHelpers` | Capture and assert sent emails |
| `EventHelpers` | Capture and assert dispatched events |
| `QueueHelpers` | Run queue workers, assert jobs |
| `MigrationHelpers` | Assert migration behavior |
| `B2BAssertions` | B2B-specific assertions (requires commercial plugin) |

**Example:**

```php
use Algoritma\ShopwareTestUtils\Traits\TimeHelpers;
use Algoritma\ShopwareTestUtils\Traits\MailHelpers;

class SubscriptionTest extends AbstractIntegrationTestCase
{
    use TimeHelpers;
    use MailHelpers;

    public function testSubscriptionRenewal(): void
    {
        // Freeze time to test time-dependent logic
        $this->freezeTime(new \DateTime('2025-01-01 00:00:00'));

        // ... create subscription ...

        // Travel forward 30 days
        $this->travelTo(new \DateTime('2025-01-31 00:00:00'));

        // Run renewal process
        $this->runScheduledTask(RenewalTask::class);

        // Assert renewal email was sent
        $this->assertMailSent('subscription_renewal');
    }
}
```

---

## 🧪 Test Base Classes

### `AbstractIntegrationTestCase`

Base class for **integration tests** (database, repositories, services).

**Features:**
- Database transaction rollback after each test
- Access to Shopware container and services
- Event capturing
- Mail capturing
- Queue testing support
- Custom assertions
- **Fixture Loading**: Load fixtures with automatic dependency resolution and container injection.

### `AbstractFunctionalTestCase`

Base class for **functional/storefront tests** (HTTP requests, controllers).

**Extends:** `AbstractIntegrationTestCase`

**Additional Features:**
- Storefront browser simulation
- HTTP request/response testing
- Session management

### `MigrationTestCase`

Base class for **migration tests**.

**Features:**
- Test migration up/down
- Assert table creation/modification
- Test data integrity
- Assert migration idempotency

---

## 🎨 Custom Assertions

The `ShopwareAssertions` trait provides Shopware-specific assertions:

```php
// Entity assertions
$this->assertEntityExists('product', $productId);

// Price assertions
$this->assertPriceEquals(19.99, $product);

// Customer assertions
$this->assertCustomerHasRole($customer, 'B2B');

// Database assertions
$this->assertDatabaseHas('product', ['id' => $productId, 'active' => 1]);
$this->assertDatabaseMissing('order', ['id' => $deletedOrderId]);

// Order assertions
$this->assertOrderState($order, 'completed');

// Rule assertions
$this->assertRuleMatches($ruleId, $salesChannelContext);

// State machine assertions
$this->assertStateMachineState($orderId, 'order_state', 'completed');

// Table structure assertions
$this->assertTableExists('custom_table');
$this->assertColumnExists('product', 'custom_field');
$this->assertIndexExists('product', 'idx_custom');
$this->assertForeignKeyExists('order_line_item', 'fk_order_id');

// Mail Template assertions
$this->assertMailTemplateExists('order_confirmation');
$this->assertMailTemplateSubjectContains('order_confirmation', 'en-GB', 'Order confirmation');
$this->assertMailTemplateContentContains('order_confirmation', 'en-GB', 'Thank you for your order', true);
```

---

## 🗂️ Directory Structure

```
src/
├── Assert/
│   └── ShopwareAssertions.php          # Custom assertions
├── Core/
│   ├── AbstractIntegrationTestCase.php # Integration test base
│   ├── AbstractFunctionalTestCase.php  # Functional test base
│   └── MigrationTestCase.php           # Migration test base
├── Factory/                             # Entity factories
│   ├── ProductFactory.php
│   ├── CustomerFactory.php
│   ├── OrderFactory.php
│   ├── CartFactory.php
│   └── ...
├── Helper/                              # Action helpers
│   ├── OrderHelper.php
│   ├── CartHelper.php
│   ├── MediaHelper.php
│   ├── StateManager.php
│   └── ...
├── Traits/                              # Reusable behaviors
│   ├── DatabaseHelpers.php
│   ├── CacheHelpers.php
│   ├── TimeHelpers.php
│   ├── EventHelpers.php
│   └── ...
└── Fixture/
    ├── FixtureInterface.php
    ├── FixtureManager.php
    ├── AbstractFixture.php
    └── ReferenceRepository.php
```

---

## 🔍 Advanced Examples

### Testing with Fixtures

```php
use Algoritma\ShopwareTestUtils\Fixture\AbstractFixture;
use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;

class MyFixture extends AbstractFixture
{
    public function load(ReferenceRepository $references): void
    {
        // Access container
        $repo = $this->getContainer()->get('product.repository');
        
        // Create data...
    }
}

class MyTest extends AbstractIntegrationTestCase
{
    public function testWithFixture(): void
    {
        $this->loadFixtures(new MyFixture());
        
        // ...
    }
}
```

### Testing with Time Travel

```php
use Algoritma\ShopwareTestUtils\Traits\TimeHelpers;

class CouponExpirationTest extends AbstractIntegrationTestCase
{
    use TimeHelpers;

    public function testCouponExpires(): void
    {
        $coupon = $this->createCoupon(['validUntil' => '2025-12-31']);

        // Test before expiration
        $this->freezeTime(new \DateTime('2025-06-01'));
        $this->assertTrue($coupon->isValid());

        // Test after expiration
        $this->travelTo(new \DateTime('2026-01-01'));
        $this->assertFalse($coupon->isValid());
    }
}
```

### Testing with Event Capture

```php
use Algoritma\ShopwareTestUtils\Traits\EventHelpers;

class ProductEventTest extends AbstractIntegrationTestCase
{
    use EventHelpers;

    public function testProductCreationDispatchesEvent(): void
    {
        $this->startCapturingEvents();

        $product = (new ProductFactory($this->getContainer()))
            ->create();

        $this->assertEventWasDispatched(ProductCreatedEvent::class);
        $event = $this->getDispatchedEvent(ProductCreatedEvent::class);
        $this->assertEquals($product->getId(), $event->getProductId());
    }
}
```

### Testing Migrations

```php
use Algoritma\ShopwareTestUtils\Core\MigrationTestCase;
use Algoritma\ShopwareTestUtils\Traits\MigrationHelpers;

class Migration1234567890Test extends MigrationTestCase
{
    use MigrationHelpers;

    public function testMigrationCreatesTable(): void
    {
        // Test idempotency (can run multiple times)
        $this->assertMigrationIsIdempotent(Migration1234567890::class);

        // Test table creation
        $this->assertMigrationAddsTable(Migration1234567890::class, 'custom_entity');

        // Test column exists
        $this->assertColumnExists('custom_entity', 'custom_field');
    }

    public function testDataIntegrity(): void
    {
        // Seed old data
        $this->seedTable('old_table', [
            ['id' => 1, 'name' => 'Test']
        ]);

        // Run migration
        $this->runMigration(Migration1234567890::class);

        // Test data was migrated correctly
        $this->assertDatabaseHas('new_table', ['id' => 1, 'name' => 'Test']);
    }
}
```

### Testing with Database Snapshots

```php
use Algoritma\ShopwareTestUtils\Traits\DatabaseHelpers;

class BulkOperationTest extends AbstractIntegrationTestCase
{
    use DatabaseHelpers;

    public function testBulkImport(): void
    {
        // Create snapshot before bulk operation
        $snapshotId = $this->snapshotTable('product');

        // Perform bulk import
        $this->importProducts($csvFile);

        // Test the import
        $this->assertDatabaseHas('product', ['sku' => 'NEW-SKU-001']);

        // Restore original state for other tests
        $this->restoreTableSnapshot($snapshotId);
    }
}
```

---

## 🛠️ Configuration

### PHPUnit Configuration

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/12.0/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Functional">
            <directory>tests/Functional</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run only integration tests
vendor/bin/phpunit --testsuite Integration

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

---

## 📖 Documentation

For detailed architecture documentation, see [ARCHITECTURE.md](ARCHITECTURE.md).

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. **Factories** should only create entities
2. **Helpers** should only perform actions
3. **Traits** should provide reusable behaviors
4. All code must follow PSR-12 coding standards
5. Add tests for new features

### Code Quality Tools

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Run static analysis
composer phpstan

# Run rector
composer rector-check
```

---

## 📄 License

This project is licensed under the MIT License.

---

## 🙏 Credits

Developed by **Algoritma** for the Shopware community.

---

## 💡 Support

For issues, questions, or feature requests, please open an issue on GitHub.
