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
            ScriptEvents::POST_INSTALL_CMD => 'generateStubs',
            ScriptEvents::POST_UPDATE_CMD => 'generateStubs',
        ];
    }

    public function generateStubs(Event $event): void
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $projectRoot = dirname((string) $vendorDir);
        $cacheDir = $projectRoot . '/var/cache';

        // Check if we're in the package itself or if it's installed as a dependency
        $packagePath = $vendorDir . '/algoritma/shopware-test-utils';
        if (is_dir($packagePath)) {
            // Package is installed as a dependency
            $packageRoot = $packagePath;
        } else {
            // We're inside the package itself
            $packageRoot = $projectRoot;
        }

        // Only generate if the package Factory directory exists
        if (! is_dir($packageRoot . '/src/Factory')) {
            return;
        }

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
