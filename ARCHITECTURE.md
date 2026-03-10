# Test Suite Architecture

## 📐 Separation of Responsibilities Principles

This test suite strictly follows the **Single Responsibility Principle (SRP)** and the **Factory/Trait** pattern:

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

### **Trait** = Actions, Assertions and test utilities
- **Responsibility**: Provide methods for performing actions on entities AND verification (assertions)
- **Naming**: `*Trait`, `*Helpers` or `*Assertions` (e.g. `OrderTrait`, `MailTrait`, `DatabaseHelpers`)
- **Action examples**: cancel order, set config, time travel
- **Assertion examples**: assertDatabaseHas, assertMailSent

**Example:**

```php
use Algoritma\ShopwareTestUtils\Traits\OrderTrait;
use Algoritma\ShopwareTestUtils\Traits\ConfigTrait;

class MyTest extends AbstractIntegrationTestCase
{
    public function test(): void
    {
        // Actions via traits
        $this->cancelOrder($orderId);
        $this->setSystemConfig('core.cart.maxQuantity', 100);

        // Assertions via traits
        $this->assertOrderState($orderId, 'cancelled');
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
└── Traits/                              # ACTIONS, ASSERTIONS & TEST UTILITIES
    ├── CartTrait.php                   # Actions on carts (clear, remove, etc.)
    ├── OrderTrait.php                  # Actions on orders (place, cancel, etc.)
    ├── MediaTrait.php                  # Actions on media (assign, delete, etc.)
    ├── ConfigTrait.php                 # Configuration management
    ├── TimeTrait.php                   # Time travel and date manipulation
    ├── ProductTrait.php                # Product management and assertions
    ├── CustomerTrait.php               # Customer management and assertions
    ├── StateMachineTrait.php           # State machine management
    ├── DatabaseHelpers.php             # DB assertions (table exists, row count)
    ├── CacheTrait.php                  # Cache assertions (key exists, cleared)
    ├── LogHelpers.php                  # Log assertions (error logged, contains)
    ├── MailTrait.php                   # Mail actions and assertions
    ├── EventTrait.php                  # Event actions and assertions
    ├── QueueTrait.php                  # Queue assertions
    └── MigrationTrait.php              # Migration testing and assertions
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

### **2. Trait Pattern (Actions and Assertions)**

```php
// ✅ CORRECT - Trait provides methods for actions and assertions
trait OrderTrait
{
    protected function cancelOrder(string $orderId): void { ... }
    protected function assertOrderState(string $orderId, string $state): void { ... }
}

// Usage in test:
class MyTest extends AbstractIntegrationTestCase
{
    public function testOrderCancellation(): void
    {
        // Action via trait
        $this->cancelOrder($orderId);

        // Assertion via trait
        $this->assertOrderState($orderId, 'cancelled');
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

// ✅ CORRECT - Use Trait
trait OrderTrait
{
    protected function cancelOrder(string $id): void { ... }  // OK!
}
```

### **2. Trait that only provides raw data**
```php
// ❌ WRONG - Trait should provide high-level actions/assertions
trait OrderTrait
{
    protected function getOrderData(string $id): array { ... } // NO! Too low level
}

// ✅ CORRECT - Use high-level methods
trait OrderTrait
{
    protected function cancelOrder(string $id): void { ... } // OK!
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

trait CartTrait  // Actions on carts
{
    protected function clearCart(Cart $cart): Cart { ... }
    protected function removeItemFromCart(Cart $cart, string $id): Cart { ... }
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

    // Trait: executes action
    $order = $this->orderPlace($cart, $context);

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

    // Trait: executes action
    $this->assignMediaToProduct($media->getId(), $product->getId(), true);

    // Assert (via trait)
    $this->assertEntityExists('product_media', ['mediaId' => $media->getId(), 'productId' => $product->getId()]);
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

    // Trait: executes transition
    $this->transitionOrderState($order->getId(), 'process');

    // Assert (via trait)
    $this->assertOrderState($order->getId(), 'in_progress');
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

        // Test data integrity (via trait)
        $this->assertMigrationDataIntegrity('old_table', 'new_table', function($oldRow) {
            return ['new_col' => $oldRow['old_col']];
        });
    }
}
```

---

## ✅ Checklist for New Components

When adding a new component, make sure:

- [ ] **Correct name**: Factory to create, Trait for actions/assertions
- [ ] **Single responsibility**: One component = one task
- [ ] **Documentation**: Clear PHPDoc on what it does
- [ ] **Testability**: The component is testable
- [ ] **Reusability**: Avoid duplications, use Trait if necessary

---

## 🎯 Guiding Principle

> **"Factory CREATES, Trait ACTS and SHARES"**

If a component does more than one of these things, it needs to be refactored.
