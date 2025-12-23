<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;

trait ConfigHelpers
{
    use KernelTestBehaviour;

    /**
     * Sets a system configuration value.
     */
    protected function setSystemConfig(string $key, $value, ?string $salesChannelId = null): void
    {
        $configService = $this->getSystemConfigService();
        $configService->set($key, $value, $salesChannelId);
    }

    /**
     * Gets a system configuration value.
     */
    protected function getSystemConfig(string $key, ?string $salesChannelId = null)
    {
        $configService = $this->getSystemConfigService();

        return $configService->get($key, $salesChannelId);
    }

    /**
     * Deletes a system configuration value.
     */
    protected function deleteSystemConfig(string $key, ?string $salesChannelId = null): void
    {
        $configService = $this->getSystemConfigService();
        $configService->delete($key, $salesChannelId);
    }

    /**
     * Sets multiple configuration values at once.
     */
    protected function setSystemConfigs(array $configs, ?string $salesChannelId = null): void
    {
        foreach ($configs as $key => $value) {
            $this->setSystemConfig($key, $value, $salesChannelId);
        }
    }

    /**
     * Executes a callback with temporary configuration, then restores original values.
     */
    protected function withConfig(array $config, callable $callback, ?string $salesChannelId = null)
    {
        $original = [];

        // Save original values and set new ones
        foreach ($config as $key => $value) {
            $original[$key] = $this->getSystemConfig($key, $salesChannelId);
            $this->setSystemConfig($key, $value, $salesChannelId);
        }

        try {
            return $callback();
        } finally {
            // Restore original values
            foreach ($original as $key => $value) {
                if ($value === null) {
                    $this->deleteSystemConfig($key, $salesChannelId);
                } else {
                    $this->setSystemConfig($key, $value, $salesChannelId);
                }
            }
        }
    }

    /**
     * Gets all configuration values for a domain.
     */
    protected function getConfigDomain(string $domain, ?string $salesChannelId = null): array
    {
        $configService = $this->getSystemConfigService();

        return $configService->getDomain($domain, $salesChannelId, true);
    }

    /**
     * Enables a feature flag.
     */
    protected function enableFeatureFlag(string $flag): void
    {
        $_SERVER[$flag] = '1';
        $_ENV[$flag] = '1';
    }

    /**
     * Disables a feature flag.
     */
    protected function disableFeatureFlag(string $flag): void
    {
        $_SERVER[$flag] = '0';
        $_ENV[$flag] = '0';
    }

    /**
     * Executes a callback with a feature flag enabled, then restores.
     */
    protected function withFeatureFlag(string $flag, callable $callback)
    {
        $originalServer = $_SERVER[$flag] ?? null;
        $originalEnv = $_ENV[$flag] ?? null;

        $this->enableFeatureFlag($flag);

        try {
            return $callback();
        } finally {
            if ($originalServer === null) {
                unset($_SERVER[$flag]);
            } else {
                $_SERVER[$flag] = $originalServer;
            }

            if ($originalEnv === null) {
                unset($_ENV[$flag]);
            } else {
                $_ENV[$flag] = $originalEnv;
            }
        }
    }

    /**
     * Clears the configuration cache.
     */
    protected function clearConfigCache(): void
    {
        $configService = $this->getSystemConfigService();

        // SystemConfigService has a cache that needs to be cleared
        if (method_exists($configService, 'clearCache')) {
            $configService->clearCache();
        }
    }

    /**
     * Sets a plugin configuration value.
     */
    protected function setPluginConfig(string $pluginName, string $key, $value, ?string $salesChannelId = null): void
    {
        $fullKey = $pluginName . '.config.' . $key;
        $this->setSystemConfig($fullKey, $value, $salesChannelId);
    }

    /**
     * Gets a plugin configuration value.
     */
    protected function getPluginConfig(string $pluginName, string $key, ?string $salesChannelId = null)
    {
        $fullKey = $pluginName . '.config.' . $key;

        return $this->getSystemConfig($fullKey, $salesChannelId);
    }

    /**
     * Gets the SystemConfigService from the container.
     */
    private function getSystemConfigService(): SystemConfigService
    {
        return $this->getContainer()->get(SystemConfigService::class);
    }
}
