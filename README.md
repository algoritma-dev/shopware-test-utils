# Shopware Test Utils

A comprehensive collection of helpers, factories, and utilities for **Shopware 6** integration and functional tests.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue)](https://www.php.net/)
[![Shopware Version](https://img.shields.io/badge/shopware-%5E6.7-blue)](https://www.shopware.com/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

---

## 📦 Installation

### Prerequisites

- PHP >= 8.2
- Shopware 6.7
- A Shopware project with a working DB configuration (the stub generator boots the kernel)

### Install in your Shopware project

```bash
composer require --dev algoritma/shopware-test-utils
```

If you plan to use the B2B factories/helpers, install Shopware Commercial (`store.shopware.com/swagcommercial`)
and make sure the plugin is active (license required).

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

        // Place order using trait helper
        $order = $this->orderPlace($cart, $context);

        // Assert order was created
        $this->orderAssertState($order, 'open');
        $this->assertDatabaseHas('order', ['id' => $order->getId()]);
    }
}
```

Short trait usage:

```php
// Cart + checkout helpers via traits
$cart = $this->createCart($context)
    ->withProduct($product->getId())
    ->create();

$this->cartAssertContainsProduct($cart, $product->getId());
$order = $this->checkoutPlaceOrder($cart, $context);
```

### Functional/Storefront Test

```php
<?php

use Algoritma\ShopwareTestUtils\Core\AbstractFunctionalTestCase;

class StorefrontCheckoutTest extends AbstractFunctionalTestCase
{
    public function testCheckoutFlow(): void
    {
        // Simulate user actions
        $this->storefrontAddToCart($productId);
        $this->storefrontProceedToCheckout();
        $this->storefrontSubmitOrder();

        $this->storefrontAssertResponseRedirects($this->storefrontBrowser()->getResponse());
    }
}
```

## ⚡ Parallel Tests (Paratest)

When `TEST_TOKEN` is present (Paratest worker), the suite switches to a per-worker database suffix (`_p{token}`).
If the database does not exist, it is created and initialized once with:
- `bin/ci system:install --drop-database --basic-setup --force --no-assign-theme`
- `bin/console dal:refresh:index --only category.indexer --no-interaction`

Custom setup commands can be injected via:
- `SW_TEST_INSTALL_COMMANDS` (separate commands with newline or `;`)
- or programmatically: `ParallelTestBootstrapper::setInstallCommands()` / `addInstallCommand()`

To ensure CLI commands like `bin/console` pick the correct worker DB, set `TEST_TOKEN` in `.env.test`
and include it in `DATABASE_URL`, for example:
`DATABASE_URL=mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_NAME}${TEST_TOKEN:-}?serverVersion=8.4`
Use a default like `TEST_TOKEN=local` to avoid a trailing underscore in non-parallel runs.

Note: The per-worker bootstrap is triggered in `AbstractIntegrationTestCase::setUpBeforeClass()`.
If your tests do not extend `AbstractIntegrationTestCase`/`AbstractFunctionalTestCase`, call
`ParallelTestBootstrapper::ensureParallelBootstrap()` in your own base class.

---

## 📚 Available Components

### 🏭 Factories (Create Entities)

Factories use the **Builder pattern** with **magic methods** to create and configure entities with an ultra-fluent API.

All factories extend `AbstractFactory` providing:
- ✨ **Magic methods**: `with*()` and `set*()` for any property
- 🎯 **Smart ID detection**: Automatically appends `Id` suffix when passing UUIDs
- 🔗 **Method chaining**: All setters return `$this`
- 📦 **Consistent API**: Same interface across all factories

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
| **B2B/** | Organization, Employee, Quote, Role, etc. |
| **Subscription/** | SubscriptionPlan, SubscriptionInterval |
| **MultiWarehouse/** | Warehouse, WarehouseGroup |
| **ReturnManagement/** | OrderReturn |

**Examples:**

```php
// Standard fluent API
$product = (new ProductFactory($container))
    ->withName('Gaming Laptop')
    ->withPrice(1499.99)
    ->withStock(50)
    ->active()
    ->create();

// Magic methods - any property!
$product = (new ProductFactory($container))
    ->withName('Laptop')
    ->withEan('1234567890123')        // Magic: sets 'ean'
    ->withManufacturerNumber('MFG-01') // Magic: sets 'manufacturerNumber'
    ->create();

// Smart UUID handling - automatically adds 'Id' suffix
$order = (new OrderFactory($container))
    ->withCustomer($customerUuid)              // → sets 'customerId'
    ->withSalesChannel($salesChannelUuid)      // → sets 'salesChannelId'
    ->withPaymentMethod($paymentMethodUuid)    // → sets 'paymentMethodId'
    ->withOrderNumber('ORD-001')               // → sets 'orderNumber' (not UUID)
    ->create();

// B2B Example
$quote = (new B2B\QuoteFactory($container))
    ->withCustomer($uuid)           // → 'customerId'
    ->withOrganization($uuid)       // → 'organizationId'
    ->withExpirationDate($date)     // Magic method
    ->create();
```

#### Custom factories (your project)

Create project-specific factories by extending `AbstractFactory` and placing them in a namespace that is
autoloaded (ideally under `autoload-dev` so the stub generator can discover them).

```php
<?php

namespace Acme\Shopware\Tests\Factory;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Acme\Shopware\Core\Content\Badge\BadgeDefinition;

class BadgeFactory extends AbstractFactory
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->data = [
            'id' => Uuid::randomHex(),
            'name' => 'Gold',
            'priority' => 10,
            'active' => true,
        ];
    }

    public function withPriority(int $priority): self
    {
        $this->data['priority'] = $priority;

        return $this;
    }

    protected function getRepositoryName(): string
    {
        return 'acme_badge.repository';
    }

    protected function getEntityName(): string
    {
        return BadgeDefinition::ENTITY_NAME;
    }
}
```

Notes:
- You can still rely on magic `with*()`/`set*()` methods for simple fields.
- For custom entities, ensure the plugin is installed/active so DAL definitions are available to the stub generator.
- Make sure your `composer.json` includes the namespace in `autoload-dev` and run `composer dump-autoload`.

Example `composer.json` snippet:

```json
{
  "autoload-dev": {
    "psr-4": {
      "Acme\\Shopware\\Tests\\": "tests/"
    }
  }
}
```

### 🔧 Helpers (Execute Actions)

Helpers perform operations on existing entities. Use the `HelperAccessor` trait for easy access in tests.

| Helper | Description |
|--------|-------------|
| `OrderHelper` | Cancel orders, mark as paid/shipped, get order details |
| `CartHelper` | Clear cart, remove items, recalculate |
| `MediaHelper` | Assign media to products, delete media |
| `StateManager` | Transition state machine states |
| `CheckoutRunner` | Execute complete checkout flows |
| `StorefrontRequestHelper` | Simulate storefront HTTP requests |
| `MigrationDataTester` | Test data integrity in migrations |
| `ConfigHelper` | Manage system configuration and feature flags |
| `TimeHelper` | Time travel and date manipulation |
| `ProductHelper` | Create and manage products |
| `CustomerHelper` | Create and manage customers |
| `SalesChannelHelper` | Create and manage sales channels |
| `MailHelper` | Send and verify emails |

**Example:**

```php
use Algoritma\ShopwareTestUtils\Traits\HelperAccessorTrait;

class MyTest extends AbstractIntegrationTestCase
{
    use HelperAccessorTrait;

    public function testOrderFlow(): void
    {
        // Access helpers easily via trait methods
        $this->orderHelper()->markOrderAsPaid($orderId);
        $this->orderHelper()->markOrderAsShipped($orderId);

        // Set configuration
        $this->configHelper()->set('core.cart.maxQuantity', 100);

        // Time travel
        $this->timeHelper()->travelForward('30 days');
    }
}
```

### ✨ Traits (Assertion Helpers)

Traits provide assertion methods for test verification. **Note:** Actions have been moved to Helper classes.

| Trait | Description |
|-------|-------------|
| `HelperAccessor` | **Provides easy access to all Helper classes** |
| `DatabaseHelpers` | Database assertions (table exists, row count, etc.) |
| `CacheTrait` | Cache assertions (key exists, cache cleared) |
| `ContextTrait` | Context management (create default context, sales channel context) |
| `TimeHelpers` | Time-related assertions (date in future/past, timestamp validity) |
| `LogHelpers` | Log assertions (error logged, warning count, log contains) |
| `MailHelpers` | Mail assertions (email sent, recipient correct) |
| `EventHelpers` | Event assertions (event dispatched, payload validation) |
| `QueueHelpers` | Queue assertions (job queued, queue empty) |
| `MigrationHelpers` | Migration assertions (idempotency, schema changes) |

**Example:**

```php
use Algoritma\ShopwareTestUtils\Traits\HelperAccessorTrait;
use Algoritma\ShopwareTestUtils\Traits\TimeTrait;
use Algoritma\ShopwareTestUtils\Traits\MailTrait;

class SubscriptionTest extends AbstractIntegrationTestCase
{
    use HelperAccessorTrait;  // Access to all helpers
    use TimeTrait;     // Time assertions
    use MailTrait;     // Mail assertions

    public function testSubscriptionRenewal(): void
    {
        // Use TimeHelper for actions
        $this->timeHelper()->freezeTime(new \DateTime('2025-01-01 00:00:00'));

        // ... create subscription ...

        // Travel forward 30 days
        $this->timeHelper()->travelForward('30 days');

        // Run renewal process
        $this->runScheduledTask(RenewalTask::class);

        // Use MailTrait trait for assertions
        $this->assertMailSent(1);
        $this->assertMailWasSent();
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
- **Fixture Loading**: Load fixtures with automatic dependency resolution and container injection
- **Reference Management**: Retrieve entities created by fixtures anywhere in your test with `getReference()` and `hasReference()`

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
│   ├── AbstractFactory.php             # Base factory with magic methods
│   ├── ProductFactory.php
│   ├── CustomerFactory.php
│   ├── OrderFactory.php
│   ├── CartFactory.php
│   ├── B2B/                            # B2B factories
│   │   ├── QuoteFactory.php
│   │   ├── OrganizationFactory.php
│   │   └── ...
│   ├── Subscription/                   # Subscription factories
│   ├── MultiWarehouse/                 # Multi-warehouse factories
│   └── ReturnManagement/               # Return management factories
├── Helper/                              # Action helpers
│   ├── OrderHelper.php
│   ├── CartHelper.php
│   ├── MediaHelper.php
│   ├── StateManager.php
│   └── ...
├── Traits/                              # Reusable behaviors
│   ├── HelperAccessor.php              # Access all helpers
│   ├── DatabaseHelpers.php
│   ├── CacheTrait.php
│   ├── ContextTrait.php
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

Fixtures allow you to load reusable test data with automatic dependency resolution. You can reference entities created by fixtures anywhere in your test while maintaining test isolation.

```php
use Algoritma\ShopwareTestUtils\Fixture\AbstractFixture;
use Algoritma\ShopwareTestUtils\Fixture\ReferenceRepository;

class ProductFixture extends AbstractFixture
{
    public function load(ReferenceRepository $references): void
    {
        // Access container
        $factory = $this->getContainer()->get('product.repository');

        // Create a product
        $productId = Uuid::randomHex();
        $factory->create([
            ['id' => $productId, 'name' => 'Test Product', 'price' => [/* ... */]]
        ], $this->getContext());

        // Store reference for later use
        $references->set('main-product', $productId);
    }

    // Optional: Define dependencies
    public function getDependencies(): array
    {
        return [CategoryFixture::class];
    }
}

class MyTest extends AbstractIntegrationTestCase
{
    public function testWithFixture(): void
    {
        // Load fixtures
        $this->loadFixtures(new ProductFixture());

        // Retrieve reference anywhere in the test
        $productId = $this->getReference('main-product');

        // Check if reference exists
        if ($this->hasReference('optional-product')) {
            $optionalProductId = $this->getReference('optional-product');
        }

        // Use the entities
        $product = $this->getService('product.repository')->search(
            new Criteria([$productId]),
            $this->getContext()
        )->first();

        $this->assertNotNull($product);
    }
}
```

**Key Features:**
- **Reference Management**: Store and retrieve entities by name using `getReference()` and `hasReference()`
- **Test Isolation**: References are automatically cleared between tests via `tearDown()`
- **Dependency Resolution**: Fixtures with dependencies are loaded in the correct order
- **Container Access**: Fixtures have full access to the Shopware container

### Testing with Time Travel

```php
use Algoritma\ShopwareTestUtils\Traits\TimeTrait;

class CouponExpirationTest extends AbstractIntegrationTestCase
{
    use TimeTrait;

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
use Algoritma\ShopwareTestUtils\Traits\EventTrait;

class ProductEventTest extends AbstractIntegrationTestCase
{
    use EventTrait;

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
use Algoritma\ShopwareTestUtils\Traits\MigrationTrait;

class Migration1234567890Test extends MigrationTestCase
{
    use MigrationTrait;

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
use Algoritma\ShopwareTestUtils\Traits\DatabaseTrait;

class BulkOperationTest extends AbstractIntegrationTestCase
{
    use DatabaseTrait;

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

### Environment

- Configure `DATABASE_URL` in your Shopware project as usual (tests and stub generation boot the kernel).
- If you rely on `.env.test`, run commands with `APP_ENV=test` or `--env=test`.
- For parallel testing, see the **Parallel Tests** section above.

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

### PHPStan Stubs

After generating stubs, add the file to your `phpstan.neon`:

```neon
parameters:
    stubFiles:
        - tests/factory-stubs.php
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

## 🧩 IDE and PHPStan Support

### Generate Factory Stubs (PHPStan + PhpStorm)

The stub generator boots the Shopware kernel and scans:
- Built-in factories in `src/Factory`
- Any custom factories found in your project's `autoload-dev` paths

Run it from your Shopware project root:

```bash
composer generate-stubs -- --env=test
```

Or call the binary directly:

```bash
vendor/bin/generate-factory-stubs --env=test
```

Default output:
- `tests/factory-stubs.php` (PHPStan stub file)
- `tests/.phpstorm.meta.php` (PhpStorm metadata)

You can change the output directory:

```bash
vendor/bin/generate-factory-stubs --env=test --output-dir=var/stubs
```

### PhpStorm Integration

Add the generated metadata to your project root (copy or include):

```php
<?php

if (file_exists(__DIR__ . '/tests/.phpstorm.meta.php')) {
    require __DIR__ . '/tests/.phpstorm.meta.php';
}
```

Import live templates from `phpstorm-live-templates.xml` (Settings > Editor > Live Templates > + > Import).

### What It Does

The stub generator:
- Uses `ComposerPluginLoader` to load Shopware plugins
- Analyzes DAL entity definitions via `DalMetadataService`
- Generates type hints for factory magic methods (`with*()` and `set*()`)
- Enables autocomplete for entity properties in factories

### When to Regenerate

Run `composer generate-stubs` after:
- Adding or changing custom factories
- Installing or updating Shopware plugins
- Modifying entity definitions

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
