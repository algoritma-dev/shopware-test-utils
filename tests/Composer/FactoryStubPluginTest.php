<?php

namespace Algoritma\ShopwareTestUtils\Tests\Composer;

use Algoritma\ShopwareTestUtils\Composer\FactoryStubPlugin;
use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use PHPUnit\Framework\TestCase;

class FactoryStubPluginTest extends TestCase
{
    public function testImplementsPluginInterface(): void
    {
        $plugin = new FactoryStubPlugin();

        $this->assertInstanceOf(PluginInterface::class, $plugin);
    }

    public function testSubscribesToPostInstallAndUpdateEvents(): void
    {
        $events = FactoryStubPlugin::getSubscribedEvents();

        $this->assertArrayHasKey('post-install-cmd', $events);
        $this->assertArrayHasKey('post-update-cmd', $events);
        $this->assertSame('generateStubs', $events['post-install-cmd']);
        $this->assertSame('generateStubs', $events['post-update-cmd']);
    }

    public function testCanBeActivated(): void
    {
        $plugin = new FactoryStubPlugin();
        $composer = $this->createStub(Composer::class);
        $io = $this->createStub(IOInterface::class);

        // Should not throw
        $plugin->activate($composer, $io);

        $this->assertTrue(true);
    }

    public function testCanBeDeactivated(): void
    {
        $plugin = new FactoryStubPlugin();
        $composer = $this->createStub(Composer::class);
        $io = $this->createStub(IOInterface::class);

        // Should not throw
        $plugin->deactivate($composer, $io);

        $this->assertTrue(true);
    }

    public function testCanBeUninstalled(): void
    {
        $plugin = new FactoryStubPlugin();
        $composer = $this->createStub(Composer::class);
        $io = $this->createStub(IOInterface::class);

        // Should not throw
        $plugin->uninstall($composer, $io);

        $this->assertTrue(true);
    }

    public function testGeneratesStubsWhenFactoryDirectoryExists(): void
    {
        $plugin = new FactoryStubPlugin();

        $config = $this->createStub(Config::class);
        $config->method('get')->with('vendor-dir')->willReturn(dirname(__DIR__, 2) . '/vendor');

        $composer = $this->createStub(Composer::class);
        $composer->method('getConfig')->willReturn($config);

        $io = $this->createMock(IOInterface::class);
        $io->expects($this->atLeastOnce())
            ->method('write')
            ->with($this->stringContains('Generating factory stubs'));

        $event = $this->createStub(Event::class);

        $plugin->activate($composer, $io);
        $plugin->generateStubs($event);
    }

    public function testSkipsGenerationWhenFactoryDirectoryDoesNotExist(): void
    {
        $plugin = new FactoryStubPlugin();

        $config = $this->createStub(Config::class);
        $config->method('get')->with('vendor-dir')->willReturn('/nonexistent/vendor');

        $composer = $this->createStub(Composer::class);
        $composer->method('getConfig')->willReturn($config);

        $io = $this->createMock(IOInterface::class);
        $io->expects($this->never())->method('write');

        $event = $this->createStub(Event::class);

        $plugin->activate($composer, $io);
        $plugin->generateStubs($event);
    }
}
