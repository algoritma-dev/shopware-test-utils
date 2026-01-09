<?php

namespace Algoritma\ShopwareTestUtils\Composer;

use Algoritma\ShopwareTestUtils\Core\FactoryStubGenerator;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

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
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $projectRoot = dirname((string) $vendorDir);

        // Package is in vendor when installed as dependency
        $packageRoot = $vendorDir . '/algoritma/shopware-test-utils';

        // Only generate if the package Factory directory exists
        if (! is_dir($packageRoot . '/src/Factory')) {
            return;
        }

        $cacheDir = $projectRoot . '/var/cache';

        $this->io->write('<info>Generating factory stubs for IDE autocomplete...</info>');

        try {
            $generator = new FactoryStubGenerator($packageRoot, $cacheDir);
            $result = $generator->generate();

            $this->io->write(sprintf('  <comment>✓</comment> PHPStan stub: %s', $result['stub']));
            $this->io->write(sprintf('  <comment>✓</comment> PhpStorm meta: %s', $result['meta']));
            $this->io->write('');
            $this->io->write('<comment>To enable PhpStorm autocomplete:</comment>');
            $this->io->write('  Copy var/cache/.phpstorm.meta.php to your project root');
            $this->io->write('  Or merge its contents into your existing .phpstorm.meta.php');
        } catch (\Exception $e) {
            $this->io->writeError(sprintf('<error>Failed to generate stubs: %s</error>', $e->getMessage()));
        }
    }
}
