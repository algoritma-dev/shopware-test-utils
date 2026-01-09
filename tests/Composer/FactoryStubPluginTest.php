<?php

namespace Algoritma\ShopwareTestUtils\Tests\Composer;

use Algoritma\ShopwareTestUtils\Composer\FactoryStubPlugin;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
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
        $this->assertSame('onPostInstall', $events['post-install-cmd']);
        $this->assertSame('onPostUpdate', $events['post-update-cmd']);
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
}
