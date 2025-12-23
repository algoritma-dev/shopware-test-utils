# Test Suite Architecture

## 📐 Principi di Separazione delle Responsabilità

Questa test suite segue rigorosamente il **Single Responsibility Principle (SRP)** e il pattern **Factory/Builder/Helper**:

### **Factory** = Crea entità/oggetti
- **Responsabilità**: Costruire e configurare nuove istanze di entità
- **Ritorna**: L'entità creata
- **Naming**: `*Factory` (es. `ProductFactory`, `CustomerFactory`)
- **Pattern**: Builder pattern per configurazioni complesse
- **Metodo finale**: `create()` per ottenere l'istanza

**Esempio:**
```php
$product = (new ProductFactory($container))
    ->withName('Test Product')
    ->withPrice(19.99)
    ->withStock(100)
    ->create();
```

---

### **Helper** = Esegue azioni su entità esistenti
- **Responsabilità**: Operazioni, trasformazioni, azioni su entità già create
- **NON crea** entità (usa Factory per quello)
- **Naming**: `*Helper` (es. `OrderHelper`, `MediaHelper`, `CartHelper`)
- **Esempi di azioni**: delete, update, assign, transition, cancel

**Esempio:**
```php
$orderHelper = new OrderHelper($container);
$orderHelper->cancelOrder($orderId);
$orderHelper->markOrderAsPaid($orderId);
$orderHelper->markOrderAsShipped($orderId);
```

---

### **Builder** = Pattern specifico per costruzioni complesse
- **Uso**: Solo quando serve costruzione step-by-step con stato mutabile
- **Nota**: In questa suite, `CartFactory` usa il pattern Builder internamente
- **Differenza**: Builder ha stato mutabile, Factory è immutabile

---

## 🗂️ Struttura Directory

```
src/TestUtils/
├── Assert/
│   └── ShopwareAssertions.php          # Asserzioni personalizzate
├── Core/
│   ├── AbstractIntegrationTestCase.php # Base per integration tests
│   ├── AbstractFunctionalTestCase.php  # Base per functional tests
│   └── MigrationTestCase.php           # Base per migration tests
├── Factory/                             # CREA ENTITÀ
│   ├── CartFactory.php                 # Crea e configura carrelli
│   ├── ProductFactory.php              # Crea prodotti
│   ├── CustomerFactory.php             # Crea clienti
│   ├── OrderFactory.php                # Crea ordini
│   ├── MediaFactory.php                # Crea media
│   └── ...
├── Helper/                              # ESEGUE AZIONI
│   ├── CartHelper.php                  # Azioni su carrelli (clear, remove, etc.)
│   ├── OrderHelper.php                 # Azioni su ordini (place, cancel, etc.)
│   ├── MediaHelper.php                 # Azioni su media (assign, delete, etc.)
│   ├── StateManager.php                # Gestione state machine
│   └── MigrationDataTester.php         # Test integrità migrazioni
└── Traits/                              # COMPORTAMENTI RIUTILIZZABILI
    ├── DatabaseHelpers.php             # Operazioni DB (truncate, snapshot)
    ├── CacheHelpers.php                # Gestione cache
    ├── TimeHelpers.php                 # Time travel per test
    ├── ConfigHelpers.php               # Gestione config
    ├── LogHelpers.php                  # Capture e assert log
    ├── MailHelpers.php                 # Capture e assert email
    ├── EventHelpers.php                # Capture e assert eventi
    ├── QueueHelpers.php                # Gestione queue
    └── MigrationHelpers.php            # Utility migrazioni
```

---

## 🎨 Pattern e Best Practices

### **1. Factory Pattern (Creazione)**

```php
// ✅ CORRETTO - Factory crea entità
class ProductFactory
{
    public function withName(string $name): self { ... }
    public function withPrice(float $price): self { ... }
    public function create(): ProductEntity { ... }
}

// Uso:
$product = (new ProductFactory($container))
    ->withName('Product')
    ->create();
```

### **2. Helper Pattern (Azioni)**

```php
// ✅ CORRETTO - Helper esegue azioni
class OrderHelper
{
    public function cancelOrder(string $orderId): void { ... }
    public function markAsPaid(string $orderId): void { ... }
    public function getOrder(string $orderId): OrderEntity { ... }
}

// Uso:
$helper = new OrderHelper($container);
$helper->cancelOrder($orderId);
```

### **3. Trait Pattern (Comportamenti)**

```php
// ✅ CORRETTO - Trait fornisce metodi riutilizzabili
trait TimeHelpers
{
    protected function freezeTime(\DateTimeInterface $at): void { ... }
    protected function travelTo(\DateTimeInterface $to): void { ... }
}

// Uso nel test:
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

## ❌ Anti-Pattern da Evitare

### **1. Factory che esegue azioni**
```php
// ❌ SBAGLIATO - Factory non dovrebbe fare azioni
class OrderFactory
{
    public function cancelOrder(string $id): void { ... }  // NO!
}

// ✅ CORRETTO - Usa Helper
class OrderHelper
{
    public function cancelOrder(string $id): void { ... }  // OK!
}
```

### **2. Helper che crea entità**
```php
// ❌ SBAGLIATO - Helper non dovrebbe creare
class MediaHelper
{
    public function createTestImage(): MediaEntity { ... }  // NO!
}

// ✅ CORRETTO - Usa Factory
class MediaFactory
{
    public function createTestImage(): MediaEntity { ... }  // OK!
}
```

### **3. Nomenclatura confusa**
```php
// ❌ SBAGLIATO - CartBuilder ma fa azioni
class CartBuilder
{
    public function clearCart(): void { ... }  // NO! Questo è un Helper
}

// ✅ CORRETTO - Separare responsabilità
class CartFactory  // Crea carrelli
{
    public function withProduct(string $id): self { ... }
    public function create(): Cart { ... }
}

class CartHelper  // Azioni su carrelli
{
    public function clear(Cart $cart): Cart { ... }
    public function removeItem(Cart $cart, string $id): Cart { ... }
}
```

---

## 📚 Esempi di Uso Corretto

### **Esempio 1: Creare e testare un ordine**

```php
public function testOrderPlacement(): void
{
    // Factory: crea entità
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

    // Helper: esegue azione
    $order = $this->placeOrder($cart, $context);

    // Assert
    $this->assertOrderState($order, 'open');
}
```

### **Esempio 2: Test con media**

```php
public function testProductWithMedia(): void
{
    // Factory: crea entità
    $media = (new MediaFactory($this->getContainer()))
        ->createTestImage('product-image');

    $product = (new ProductFactory($this->getContainer()))
        ->create();

    // Helper: esegue azione
    $mediaHelper = new MediaHelper($this->getContainer());
    $mediaHelper->assignToProduct($media->getId(), $product->getId(), true);

    // Assert
    $this->assertProductHasMedia($product->getId(), $media->getId());
}
```

### **Esempio 3: Test con state machine**

```php
public function testOrderStateMachine(): void
{
    // Factory: crea ordine
    $order = (new OrderFactory($this->getContainer()))
        ->withState('open')
        ->create();

    // Helper: esegue transizione
    $stateManager = new StateManager($this->getContainer());
    $stateManager->transitionOrderState($order->getId(), 'process');

    // Assert
    $this->assertStateMachineState($order->getId(), 'in_progress');
}
```

---

## 🔄 Migration Testing

Per i test delle migrazioni, seguire questa struttura:

```php
class MyMigrationTest extends MigrationTestCase
{
    use MigrationHelpers;

    public function testMigrationAddsTable(): void
    {
        // Verifica idempotenza
        $this->assertMigrationIsIdempotent(MyMigration::class);

        // Verifica creazione tabella
        $this->assertMigrationAddsTable(MyMigration::class, 'my_new_table');

        // Test integrità dati
        $tester = new MigrationDataTester($this->getConnection());
        $tester->testDataIntegrity('old_table', 'new_table', function($oldRow) {
            return ['new_col' => $oldRow['old_col']];
        });
    }
}
```

---

## ✅ Checklist per Nuovi Componenti

Quando aggiungi un nuovo componente, assicurati:

- [ ] **Nome corretto**: Factory per creare, Helper per azioni
- [ ] **Singola responsabilità**: Un componente = un compito
- [ ] **Documentazione**: PHPDoc chiara su cosa fa
- [ ] **Test**: Il componente è testabile
- [ ] **Riusabilità**: Evita duplicazioni, usa Trait se necessario

---

## 🎯 Principio Guida

> **"Factory CREA, Helper AGISCE, Trait CONDIVIDE"**

Se un componente fa più di una di queste cose, va refactorato.
