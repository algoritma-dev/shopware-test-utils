# Test Suite Architecture

## 📐 Separation of Responsibilities Principles

This test suite strictly follows the **Single Responsibility Principle (SRP)** and the **Factory/Builder/Helper** pattern:

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

### **Helper** = Executes actions on existing entities
- **Responsibility**: Operations, transformations, actions on already created entities
- **Does NOT create** entities (use Factory for that)
- **Naming**: `*Helper` (e.g. `OrderHelper`, `MediaHelper`, `CartHelper`)
- **Action examples**: delete, update, assign, transition, cancel

**Example:**
```php
$orderHelper = new OrderHelper($container);
$orderHelper->cancelOrder($orderId);
$orderHelper->markOrderAsPaid($orderId);
$orderHelper->markOrderAsShipped($orderId);
```

---

### **Builder** = Specific pattern for complex constructions
- **Usage**: Only when step-by-step construction with mutable state is needed
- **Note**: In this suite, `CartFactory` uses the Builder pattern internally
- **Difference**: Builder has mutable state, Factory is immutable

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
│   ├── StateManager.php                # State machine management
│   └── MigrationDataTester.php         # Migration integrity testing
└── Traits/                              # REUSABLE BEHAVIORS
    ├── DatabaseHelpers.php             # DB operations (truncate, snapshot)
    ├── CacheHelpers.php                # Cache management
    ├── TimeHelpers.php                 # Time travel for tests
    ├── ConfigHelpers.php               # Config management
    ├── LogHelpers.php                  # Capture and assert logs
    ├── MailHelpers.php                 # Capture and assert emails
    ├── EventHelpers.php                # Capture and assert events
    ├── QueueHelpers.php                # Queue management
    └── MigrationHelpers.php            # Migration utilities
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

// Usage:
$helper = new OrderHelper($container);
$helper->cancelOrder($orderId);
```

### **3. Trait Pattern (Behaviors)**

```php
// ✅ CORRECT - Trait provides reusable methods
trait TimeHelpers
{
    protected function freezeTime(\DateTimeInterface $at): void { ... }
    protected function travelTo(\DateTimeInterface $to): void { ... }
}

// Usage in test:
class MyTest extends AbstractIntegrationTestCase
{
    use TimeHelpers;

    public function testExpiration(): void
    {
        $this->freezeTime(new \DateTime('2025-01-01'));
        // ...
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

### **2. Helper that creates entities**
```php
// ❌ WRONG - Helper should not create
class MediaHelper
{
    public function createTestImage(): MediaEntity { ... }  // NO!
}

// ✅ CORRECT - Use Factory
class MediaFactory
{
    public function createTestImage(): MediaEntity { ... }  // OK!
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
