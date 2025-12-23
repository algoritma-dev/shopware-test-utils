# Test Suite Architecture

## 📐 Separation of Responsibilities Principles

This test suite strictly follows the **Single Responsibility Principle (SRP)** and the **Factory/Helper/Trait** pattern:

### **Factory** = Creates entities/objects
- **Responsibility**: Build and configure new entity instances
- **Returns**: The created entity
- **Naming**: `*Factory` (e.g. `ProductFactory`, `CustomerFactory`)
- **Pattern**: Builder pattern for complex configurations
- **Final method**: `create()` to obtain the instance

**Example:**
```php
$product = (new ProductFactory($container))
    ->withName('Test Product')
    ->withPrice(19.99)
    ->withStock(100)
    ->create();
```

---

### **Helper** = Executes actions/operations
- **Responsibility**: Operations, transformations, actions (on entities, system config, time, etc.)
- **Can create** simple data structures or perform actions on entities
- **Naming**: `*Helper` (e.g. `OrderHelper`, `ConfigHelper`, `TimeHelper`)
- **Action examples**: cancel order, set config, time travel, send email
- **Access**: Use `HelperAccessor` trait for convenient access

**Example:**
```php
use Algoritma\ShopwareTestUtils\Traits\HelperAccessor;

class MyTest extends AbstractIntegrationTestCase
{
    use HelperAccessor;

    public function test(): void
    {
        // Actions on entities
        $this->orderHelper()->cancelOrder($orderId);

        // Configuration management
        $this->configHelper()->set('core.cart.maxQuantity', 100);

        // Time manipulation
        $this->timeHelper()->travelForward('30 days');
    }
}
```

---

### **Trait** = Assertions and test utilities
- **Responsibility**: Provide assertion methods for test verification
- **Does NOT perform** actions (actions are in Helpers)
- **Naming**: `*Helpers` or `*Assertions` (e.g. `DatabaseHelpers`, `TimeHelpers`)
- **Examples**: assertDatabaseHas, assertMailSent, assertDateInFuture

**Example:**
```php
use Algoritma\ShopwareTestUtils\Traits\DatabaseHelpers;
use Algoritma\ShopwareTestUtils\Traits\TimeHelpers;

class MyTest extends AbstractIntegrationTestCase
{
    use DatabaseHelpers;
    use TimeHelpers;

    public function test(): void
    {
        // Database assertions
        $this->assertDatabaseHas('product', ['id' => $productId]);

        // Time assertions
        $this->assertDateInFuture($expirationDate);
    }
}
```

---

## 🗂️ Directory Structure

```
src/TestUtils/
├── Assert/
│   └── ShopwareAssertions.php          # Custom assertions
├── Core/
│   ├── AbstractIntegrationTestCase.php # Base for integration tests
│   ├── AbstractFunctionalTestCase.php  # Base for functional tests
│   └── MigrationTestCase.php           # Base for migration tests
├── Factory/                             # CREATES ENTITIES
│   ├── CartFactory.php                 # Creates and configures carts
│   ├── ProductFactory.php              # Creates products
│   ├── CustomerFactory.php             # Creates customers
│   ├── OrderFactory.php                # Creates orders
│   ├── MediaFactory.php                # Creates media
│   └── ...
├── Helper/                              # EXECUTES ACTIONS
│   ├── CartHelper.php                  # Actions on carts (clear, remove, etc.)
│   ├── OrderHelper.php                 # Actions on orders (place, cancel, etc.)
│   ├── MediaHelper.php                 # Actions on media (assign, delete, etc.)
│   ├── ConfigHelper.php                # Configuration management
│   ├── TimeHelper.php                  # Time travel and date manipulation
│   ├── ProductHelper.php               # Product creation and management
│   ├── CustomerHelper.php              # Customer creation and management
│   ├── StateManager.php                # State machine management
│   └── MigrationDataTester.php         # Migration integrity testing
└── Traits/                              # ASSERTIONS & TEST UTILITIES
    ├── HelperAccessor.php              # Convenient access to all helpers
    ├── DatabaseHelpers.php             # DB assertions (table exists, row count)
    ├── CacheHelpers.php                # Cache assertions (key exists, cleared)
    ├── TimeHelpers.php                 # Time assertions (date in future/past)
    ├── LogHelpers.php                  # Log assertions (error logged, contains)
    ├── MailHelpers.php                 # Mail assertions (email sent, recipient)
    ├── EventHelpers.php                # Event assertions (dispatched, payload)
    ├── QueueHelpers.php                # Queue assertions (job queued, empty)
    └── MigrationHelpers.php            # Migration assertions (idempotency, schema)
```

---

## 🎨 Patterns and Best Practices

### **1. Factory Pattern (Creation)**

```php
// ✅ CORRECT - Factory creates entities
class ProductFactory
{
    public function withName(string $name): self { ... }
    public function withPrice(float $price): self { ... }
    public function create(): ProductEntity { ... }
}

// Usage:
$product = (new ProductFactory($container))
    ->withName('Product')
    ->create();
```

### **2. Helper Pattern (Actions)**

```php
// ✅ CORRECT - Helper executes actions
class OrderHelper
{
    public function cancelOrder(string $orderId): void { ... }
    public function markAsPaid(string $orderId): void { ... }
    public function getOrder(string $orderId): OrderEntity { ... }
}

class ConfigHelper
{
    public function set(string $key, $value): void { ... }
    public function get(string $key) { ... }
}

class TimeHelper
{
    public function freezeTime(\DateTimeInterface $at): void { ... }
    public function travelForward(string $interval): void { ... }
}

// Usage with HelperAccessor:
class MyTest extends AbstractIntegrationTestCase
{
    use HelperAccessor;

    public function test(): void
    {
        $this->orderHelper()->cancelOrder($orderId);
        $this->configHelper()->set('core.cart.maxQuantity', 100);
        $this->timeHelper()->travelForward('30 days');
    }
}
```

### **3. Trait Pattern (Assertions)**

```php
// ✅ CORRECT - Trait provides assertion methods
trait TimeHelpers
{
    protected function assertDateInFuture(\DateTimeInterface $date): void { ... }
    protected function assertDateInPast(\DateTimeInterface $date): void { ... }
}

trait DatabaseHelpers
{
    protected function assertDatabaseHas(string $table, array $data): void { ... }
    protected function assertTableExists(string $table): void { ... }
}

// Usage in test:
class MyTest extends AbstractIntegrationTestCase
{
    use HelperAccessor;  // For actions
    use TimeHelpers;     // For time assertions
    use DatabaseHelpers; // For DB assertions

    public function testExpiration(): void
    {
        // Action via helper
        $this->timeHelper()->freezeTime(new \DateTime('2025-01-01'));

        // Assertion via trait
        $this->assertDateInFuture($expirationDate);
        $this->assertDatabaseHas('product', ['id' => $productId]);
    }
}
```

---

## ❌ Anti-Patterns to Avoid

### **1. Factory that executes actions**
```php
// ❌ WRONG - Factory should not perform actions
class OrderFactory
{
    public function cancelOrder(string $id): void { ... }  // NO!
}

// ✅ CORRECT - Use Helper
class OrderHelper
{
    public function cancelOrder(string $id): void { ... }  // OK!
}
```

### **2. Trait that performs actions**
```php
// ❌ WRONG - Trait should not perform actions (use Helper instead)
trait ConfigHelpers
{
    protected function setSystemConfig(string $key, $value): void { ... }  // NO!
}

// ✅ CORRECT - Actions go in Helper, accessed via HelperAccessor
class ConfigHelper
{
    public function set(string $key, $value): void { ... }  // OK!
}

// Usage:
class MyTest extends AbstractIntegrationTestCase
{
    use HelperAccessor;

    public function test(): void
    {
        $this->configHelper()->set('key', 'value');  // OK!
    }
}
```

### **3. Confusing nomenclature**
```php
// ❌ WRONG - CartBuilder but performs actions
class CartBuilder
{
    public function clearCart(): void { ... }  // NO! This is a Helper
}

// ✅ CORRECT - Separate responsibilities
class CartFactory  // Creates carts
{
    public function withProduct(string $id): self { ... }
    public function create(): Cart { ... }
}

class CartHelper  // Actions on carts
{
    public function clear(Cart $cart): Cart { ... }
    public function removeItem(Cart $cart, string $id): Cart { ... }
}
```

---

## 📚 Correct Usage Examples

### **Example 1: Create and test an order**

```php
public function testOrderPlacement(): void
{
    // Factory: creates entities
    $customer = (new CustomerFactory($this->getContainer()))
        ->withEmail('test@example.com')
        ->create();

    $product = (new ProductFactory($this->getContainer()))
        ->withName('Test Product')
        ->withPrice(19.99)
        ->create();

    $context = $this->createAuthenticatedContext($customer);

    $cart = $this->createCart($context)
        ->withProduct($product->getId())
        ->create();

    // Helper: executes action
    $order = $this->placeOrder($cart, $context);

    // Assert
    $this->assertOrderState($order, 'open');
}
```

### **Example 2: Test with media**

```php
public function testProductWithMedia(): void
{
    // Factory: creates entities
    $media = (new MediaFactory($this->getContainer()))
        ->createTestImage('product-image');

    $product = (new ProductFactory($this->getContainer()))
        ->create();

    // Helper: executes action
    $mediaHelper = new MediaHelper($this->getContainer());
    $mediaHelper->assignToProduct($media->getId(), $product->getId(), true);

    // Assert
    $this->assertProductHasMedia($product->getId(), $media->getId());
}
```

### **Example 3: Test with state machine**

```php
public function testOrderStateMachine(): void
{
    // Factory: creates order
    $order = (new OrderFactory($this->getContainer()))
        ->withState('open')
        ->create();

    // Helper: executes transition
    $stateManager = new StateManager($this->getContainer());
    $stateManager->transitionOrderState($order->getId(), 'process');

    // Assert
    $this->assertStateMachineState($order->getId(), 'in_progress');
}
```

---

## 🔄 Migration Testing

For migration tests, follow this structure:

```php
class MyMigrationTest extends MigrationTestCase
{
    use MigrationHelpers;

    public function testMigrationAddsTable(): void
    {
        // Verify idempotency
        $this->assertMigrationIsIdempotent(MyMigration::class);

        // Verify table creation
        $this->assertMigrationAddsTable(MyMigration::class, 'my_new_table');

        // Test data integrity
        $tester = new MigrationDataTester($this->getConnection());
        $tester->testDataIntegrity('old_table', 'new_table', function($oldRow) {
            return ['new_col' => $oldRow['old_col']];
        });
    }
}
```

---

## ✅ Checklist for New Components

When adding a new component, make sure:

- [ ] **Correct name**: Factory to create, Helper for actions
- [ ] **Single responsibility**: One component = one task
- [ ] **Documentation**: Clear PHPDoc on what it does
- [ ] **Testability**: The component is testable
- [ ] **Reusability**: Avoid duplications, use Trait if necessary

---

## 🎯 Guiding Principle

> **"Factory CREATES, Helper ACTS, Trait SHARES"**

If a component does more than one of these things, it needs to be refactored.
