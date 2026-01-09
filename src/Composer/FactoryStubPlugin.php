<?php

namespace Algoritma\ShopwareTestUtils\Composer;

use Algoritma\ShopwareTestUtils\Core\FactoryStubGenerator;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use function exec;

/**
 * Composer plugin that automatically generates factory stubs after install/update.
 */
class FactoryStubPlugin implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;

    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Nothing to do
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstall',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdate',
        ];
    }

    public function onPostInstall(Event $event): void
    {
        $this->generateStubs();
    }

    public function onPostUpdate(Event $event): void
    {
        $this->generateStubs();
    }

    private function generateStubs(): void
    {
        $binDir = $this->composer->getConfig()->get('bin-dir');

        exec($binDir . '/generate-factory-stubs');

        exit(0);
    }
}
