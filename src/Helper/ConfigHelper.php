<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for managing system configuration in tests.
 */
class ConfigHelper
{
    private readonly SystemConfigService $configService;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->configService = $this->container->get(SystemConfigService::class);
    }

    /**
     * Sets a system configuration value.
     */
    public function set(string $key, string $value, ?string $salesChannelId = null): void
    {
        $this->configService->set($key, $value, $salesChannelId);
    }

    /**
     * Gets a system configuration value.
     */
    public function get(string $key, ?string $salesChannelId = null): mixed
    {
        return $this->configService->get($key, $salesChannelId);
    }

    /**
     * Deletes a system configuration value.
     */
    public function delete(string $key, ?string $salesChannelId = null): void
    {
        $this->configService->delete($key, $salesChannelId);
    }

    /**
     * Sets multiple configuration values at once.
     */
    /**
     * @param array<string, string> $configs
     */
    public function setMultiple(array $configs, ?string $salesChannelId = null): void
    {
        foreach ($configs as $key => $value) {
            $this->set($key, $value, $salesChannelId);
        }
    }

    /**
     * Executes a callback with temporary configuration, then restores original values.
     */
    /**
     * @param array<string, string> $config
     * @param callable(): mixed $callback
     */
    public function withConfig(array $config, callable $callback, ?string $salesChannelId = null): mixed
    {
        $original = [];

        // Save original values and set new ones
        foreach ($config as $key => $value) {
            $original[$key] = $this->get($key, $salesChannelId);
            $this->set($key, $value, $salesChannelId);
        }

        try {
            return $callback();
        } finally {
            // Restore original values
            foreach ($original as $key => $value) {
                if ($value === null) {
                    $this->delete($key, $salesChannelId);
                } else {
                    $this->set($key, $value, $salesChannelId);
                }
            }
        }
    }

    /**
     * Gets all configuration values for a domain.
     *
     * @return array<string, mixed>
     */
    public function getDomain(string $domain, ?string $salesChannelId = null): array
    {
        /** @var array<string, mixed> $values */
        $values = $this->configService->getDomain($domain, $salesChannelId, true);

        return $values;
    }

    /**
     * Enables a feature flag.
     */
    public function enableFeatureFlag(string $flag): void
    {
        $_SERVER[$flag] = '1';
        $_ENV[$flag] = '1';
    }

    /**
     * Disables a feature flag.
     */
    public function disableFeatureFlag(string $flag): void
    {
        $_SERVER[$flag] = '0';
        $_ENV[$flag] = '0';
    }

    /**
     * Executes a callback with a feature flag enabled, then restores.
     */
    /**
     * @param callable(): mixed $callback
     */
    public function withFeatureFlag(string $flag, callable $callback): mixed
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
    public function clearCache(): void
    {
        // SystemConfigService has a cache that needs to be cleared
        if (method_exists($this->configService, 'clearCache')) {
            $this->configService->clearCache();
        }
    }

    /**
     * Sets a plugin configuration value.
     */
    public function setPluginConfig(string $pluginName, string $key, string $value, ?string $salesChannelId = null): void
    {
        $fullKey = $pluginName . '.config.' . $key;
        $this->set($fullKey, $value, $salesChannelId);
    }

    /**
     * Gets a plugin configuration value.
     */
    public function getPluginConfig(string $pluginName, string $key, ?string $salesChannelId = null): mixed
    {
        $fullKey = $pluginName . '.config.' . $key;

        return $this->get($fullKey, $salesChannelId);
    }
}
