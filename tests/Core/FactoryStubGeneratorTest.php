<?php

namespace Algoritma\ShopwareTestUtils\Tests\Core;

use Algoritma\ShopwareTestUtils\Core\FactoryStubGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FactoryStubGeneratorTest extends TestCase
{
    private string $testCacheDir;
    private string $projectRoot;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 2);
        $this->testCacheDir = sys_get_temp_dir() . '/factory-stub-test-' . uniqid();

        if (!is_dir($this->testCacheDir)) {
            mkdir($this->testCacheDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testCacheDir)) {
            array_map('unlink', glob($this->testCacheDir . '/*') ?: []);
            rmdir($this->testCacheDir);
        }
    }

    #[Test]
    public function it_generates_stub_and_meta_files(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stub', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertFileExists($result['stub']);
        $this->assertFileExists($result['meta']);
        $this->assertStringEndsWith('factory-stubs.php', $result['stub']);
        $this->assertStringEndsWith('.phpstorm.meta.php', $result['meta']);
    }

    #[Test]
    public function stub_file_contains_valid_php(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        $this->assertStringStartsWith('<?php', $content);
        $this->assertStringContainsString('namespace Algoritma\ShopwareTestUtils\Factory', $content);
    }

    #[Test]
    public function meta_file_contains_valid_phpstorm_metadata(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['meta']);

        $this->assertStringStartsWith('<?php', $content);
        $this->assertStringContainsString('namespace PHPSTORM_META', $content);
        $this->assertStringContainsString('override(', $content);
        $this->assertStringContainsString('AbstractFactory::__call', $content);
    }

    #[Test]
    public function meta_file_contains_expected_arguments(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['meta']);

        $this->assertStringContainsString('expectedArguments(', $content);
        $this->assertStringContainsString("'withEmail'", $content);
        $this->assertStringContainsString("'setEmail'", $content);
        $this->assertStringContainsString("'withCustomerNumber'", $content);
    }

    #[Test]
    public function stub_file_contains_customer_factory_methods(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // Check for CustomerFactory stub
        $this->assertStringContainsString('abstract class CustomerFactory', $content);

        // Check for expected methods based on CustomerFactory properties
        $this->assertStringContainsString('@method static withCustomerNumber', $content);
        $this->assertStringContainsString('@method static withEmail', $content);
        $this->assertStringContainsString('@method static withFirstName', $content);
        $this->assertStringContainsString('@method static withLastName', $content);
    }

    #[Test]
    public function stub_file_contains_product_factory_methods(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // Check for ProductFactory stub
        $this->assertStringContainsString('abstract class ProductFactory', $content);

        // Check for expected methods
        $this->assertStringContainsString('@method static withProductNumber', $content);
        $this->assertStringContainsString('@method static withStock', $content);
        $this->assertStringContainsString('@method static withName', $content);
        $this->assertStringContainsString('@method static withDescription', $content);
    }

    #[Test]
    public function stub_file_contains_both_with_and_set_methods(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // Verify both with* and set* methods are generated
        $this->assertStringContainsString('@method static withEmail', $content);
        $this->assertStringContainsString('@method static setEmail', $content);
    }

    #[Test]
    public function stub_file_does_not_contain_abstract_factory(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // AbstractFactory itself should not be in the stub
        $this->assertStringNotContainsString('abstract class AbstractFactory', $content);
    }

    #[Test]
    public function creates_cache_directory_if_not_exists(): void
    {
        $nonExistentDir = $this->testCacheDir . '/nested/dir';

        $generator = new FactoryStubGenerator($this->projectRoot, $nonExistentDir);
        $result = $generator->generate();

        $this->assertDirectoryExists($nonExistentDir);
        $this->assertFileExists($result['stub']);
        $this->assertFileExists($result['meta']);
    }

    #[Test]
    public function stub_file_has_proper_phpdoc_format(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // Check for proper PHPDoc structure
        $this->assertMatchesRegularExpression('/\/\*\*\s+\*\s+@method/', $content);
        $this->assertMatchesRegularExpression('/@method\s+static\s+with\w+\(mixed\s+\$value\)/', $content);
    }

    #[Test]
    public function regenerating_overwrites_existing_files(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        // First generation
        $result1 = $generator->generate();
        $stubContent1 = file_get_contents($result1['stub']);
        $metaContent1 = file_get_contents($result1['meta']);
        $stubMtime1 = filemtime($result1['stub']);
        $metaMtime1 = filemtime($result1['meta']);

        // Wait a moment to ensure different timestamp
        sleep(1);

        // Second generation
        $result2 = $generator->generate();
        $stubContent2 = file_get_contents($result2['stub']);
        $metaContent2 = file_get_contents($result2['meta']);
        $stubMtime2 = filemtime($result2['stub']);
        $metaMtime2 = filemtime($result2['meta']);

        $this->assertSame($result1['stub'], $result2['stub']);
        $this->assertSame($result1['meta'], $result2['meta']);
        $this->assertSame($stubContent1, $stubContent2);
        $this->assertSame($metaContent1, $metaContent2);
        $this->assertGreaterThan($stubMtime1, $stubMtime2);
        $this->assertGreaterThan($metaMtime1, $metaMtime2);
    }

    #[Test]
    public function stub_methods_have_correct_capitalization(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // Property 'customerNumber' should become 'withCustomerNumber' (capital C, capital N)
        $this->assertStringContainsString('withCustomerNumber', $content);

        // Property 'firstName' should become 'withFirstName' (capital F, capital N)
        $this->assertStringContainsString('withFirstName', $content);

        // Should not have lowercase after 'with'
        $this->assertStringNotContainsString('withcustomerNumber', $content);
    }

    #[Test]
    public function meta_methods_are_sorted_alphabetically(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['meta']);

        // Extract all method names from the meta file
        preg_match_all("/'(with\w+|set\w+)'/", $content, $matches);
        $methods = $matches[1];

        // Verify they are sorted
        $sortedMethods = $methods;
        sort($sortedMethods);

        $this->assertEquals($sortedMethods, $methods);
    }
}
