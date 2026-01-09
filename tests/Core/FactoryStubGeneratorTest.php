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

        if (! is_dir($this->testCacheDir)) {
            mkdir($this->testCacheDir, 0o755, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testCacheDir)) {
            $this->removeDirectory($this->testCacheDir);
        }
    }

    #[Test]
    public function itGeneratesStubAndMetaFiles(): void
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
    public function stubFileContainsValidPhp(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        $this->assertStringStartsWith('<?php', $content);
        $this->assertStringContainsString('namespace Algoritma\ShopwareTestUtils\Factory', $content);
    }

    #[Test]
    public function metaFileContainsValidPhpstormMetadata(): void
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
    public function metaFileContainsExpectedArguments(): void
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
    public function stubFileContainsCustomerFactoryMethods(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // Check for CustomerFactory stub
        $this->assertStringContainsString('class CustomerFactory', $content);
        $this->assertStringContainsString('extends', $content);

        // Check for expected methods with self return type for chaining
        $this->assertStringContainsString('@method self withCustomerNumber', $content);
        $this->assertStringContainsString('@method self withEmail', $content);
        $this->assertStringContainsString('@method self withFirstName', $content);
        $this->assertStringContainsString('@method self withLastName', $content);
    }

    #[Test]
    public function stubFileContainsProductFactoryMethods(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // Check for ProductFactory stub
        $this->assertStringContainsString('class ProductFactory', $content);

        // Check for expected methods with self return type
        $this->assertStringContainsString('@method self withProductNumber', $content);
        $this->assertStringContainsString('@method self withStock', $content);
        $this->assertStringContainsString('@method self withName', $content);
        $this->assertStringContainsString('@method self withDescription', $content);
    }

    #[Test]
    public function stubFileContainsBothWithAndSetMethods(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // Verify both with* and set* methods are generated with self return type
        $this->assertStringContainsString('@method self withEmail', $content);
        $this->assertStringContainsString('@method self setEmail', $content);
    }

    #[Test]
    public function stubFileDoesNotContainAbstractFactory(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // AbstractFactory itself should not be in the stub
        $this->assertStringNotContainsString('class AbstractFactory', $content);
    }

    #[Test]
    public function createsCacheDirectoryIfNotExists(): void
    {
        $nonExistentDir = $this->testCacheDir . '/nested/dir';

        $generator = new FactoryStubGenerator($this->projectRoot, $nonExistentDir);
        $result = $generator->generate();

        $this->assertDirectoryExists($nonExistentDir);
        $this->assertFileExists($result['stub']);
        $this->assertFileExists($result['meta']);
    }

    #[Test]
    public function stubFileHasProperPhpdocFormat(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // Check for proper PHPDoc structure with self return type
        self::assertStringContainsString('withSalesChannel', $content);
        // method name 'withSalesChannelId' should become 'withSalesChannel' (without an Id suffix)
        self::assertStringNotContainsString('withSalesChannelId', $content);
        $this->assertMatchesRegularExpression('/\/\*\*\s+\*\s+@method/', $content);
        $this->assertMatchesRegularExpression('/@method\s+self\s+with\w+\(mixed\s+\$value\)/', $content);
    }

    #[Test]
    public function regeneratingOverwritesExistingFiles(): void
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
    public function stubMethodsHaveCorrectCapitalization(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['stub']);

        // Property 'salesChannelId' should become 'withSalesChannel' (capital C, without an Id suffix)
        $this->assertStringContainsString('withSalesChannel', $content);

        // Property 'customerNumber' should become 'withCustomerNumber' (capital C, capital N)
        $this->assertStringContainsString('withCustomerNumber', $content);

        // Property 'firstName' should become 'withFirstName' (capital F, capital N)
        $this->assertStringContainsString('withFirstName', $content);

        // Should not have lowercase after 'with'
        $this->assertStringNotContainsString('withcustomerNumber', $content);
    }

    #[Test]
    public function metaMethodsAreSortedAlphabetically(): void
    {
        $generator = new FactoryStubGenerator($this->projectRoot, $this->testCacheDir);

        $result = $generator->generate();
        $content = file_get_contents($result['meta']);

        // Extract all method names from the meta file
        preg_match_all("/'(with\\w+|set\\w+)'/", $content, $matches);
        $methods = $matches[1];

        // Verify they are sorted
        $sortedMethods = $methods;
        sort($sortedMethods);

        $this->assertEquals($sortedMethods, $methods);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.') {
                continue;
            }
            if ($item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
