<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Command;

use Algoritma\ShopwareTestUtils\Command\GenerateStubsCommand;
use Algoritma\ShopwareTestUtils\Core\DalMetadataService;
use Algoritma\ShopwareTestUtils\Core\FactoryRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateStubsCommandTest extends TestCase
{
    private string $tempDir;

    private FactoryRegistry $factoryRegistry;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/sw-test-suite-' . uniqid();
        mkdir($this->tempDir, 0o777, true);
        mkdir($this->tempDir . '/tests', 0o777, true);
        // Do not create .phpstorm.meta.php as a directory, it is expected to be a file

        $this->factoryRegistry = new FactoryRegistry();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testExecuteSuccess(): void
    {
        $metadataService = $this->createMock(DalMetadataService::class);
        $metadataService->method('getEntityProperties')->willReturn([]);
        $metadataService->method('getEntityRelations')->willReturn([]);

        $command = new GenerateStubsCommand($this->tempDir, $metadataService, $this->factoryRegistry);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([], ['interactive' => false, 'decorated' => false]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Factory stubs generated successfully', $commandTester->getDisplay());
    }

    public function testExecuteGeneratesFiles(): void
    {
        $metadataService = $this->createMock(DalMetadataService::class);
        $metadataService->method('getEntityProperties')->willReturn([]);
        $metadataService->method('getEntityRelations')->willReturn([]);

        $command = new GenerateStubsCommand($this->tempDir, $metadataService, $this->factoryRegistry);
        $commandTester = new CommandTester($command);

        $commandTester->execute([], ['interactive' => false, 'decorated' => false]);

        $stubPath = $this->tempDir . '/tests/factory-stubs.php';
        $metaPath = $this->tempDir . '/tests/.phpstorm.meta.php';

        $this->assertFileExists($stubPath);
        $this->assertFileExists($metaPath);
    }

    public function testExecuteFailure(): void
    {
        // Use a file path that is definitely not writable
        $nonExistentDir = '/non-existent-directory-' . uniqid();
        $command = new GenerateStubsCommand($nonExistentDir, $this->createMock(DalMetadataService::class), $this->factoryRegistry);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([], ['interactive' => false, 'decorated' => false]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Error generating stubs', $commandTester->getDisplay());
    }

    public function testCommandConfiguration(): void
    {
        $metadataService = $this->createMock(DalMetadataService::class);
        $command = new GenerateStubsCommand($this->tempDir, $metadataService, $this->factoryRegistry);

        $this->assertSame('alg:sw-test-util:generate-stubs', $command->getName());
        $this->assertSame('Generates PHPStan and PhpStorm stub files for factory classes', $command->getDescription());
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
