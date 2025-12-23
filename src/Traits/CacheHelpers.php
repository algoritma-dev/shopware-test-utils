<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

trait CacheHelpers
{
    use KernelTestBehaviour;
    
    /**
     * Clears all cache pools.
     */
    protected function clearCache(): void
    {
        $cacheDirectory = self::getContainer()->getParameter('kernel.cache_dir');

        if (is_dir($cacheDirectory)) {
            $this->recursiveRemoveDirectory($cacheDirectory);
        }
    }

    /**
     * Clears a specific cache pool.
     */
    protected function clearSpecificCache(string $poolName): void
    {
        $pool = self::getContainer()->get($poolName);

        if ($pool instanceof AdapterInterface) {
            $pool->clear();
        }
    }

    /**
     * Warms up the cache.
     */
    protected function warmUpCache(): void
    {
        $warmer = self::getContainer()->get('cache_warmer');
        $warmer->warmUp(self::getContainer()->getParameter('kernel.cache_dir'));
    }

    /**
     * Asserts that a cache key exists.
     */
    protected function assertCached(string $key, string $poolName = 'cache.app'): void
    {
        $pool = self::getContainer()->get($poolName);

        if (! $pool instanceof AdapterInterface) {
            Assert::fail("Cache pool {$poolName} is not an instance of AdapterInterface");
        }

        $item = $pool->getItem($key);
        Assert::assertTrue($item->isHit(), sprintf('Cache key "%s" was not found in pool "%s"', $key, $poolName));
    }

    /**
     * Asserts that a cache key does not exist.
     */
    protected function assertNotCached(string $key, string $poolName = 'cache.app'): void
    {
        $pool = self::getContainer()->get($poolName);

        if (! $pool instanceof AdapterInterface) {
            Assert::fail("Cache pool {$poolName} is not an instance of AdapterInterface");
        }

        $item = $pool->getItem($key);
        Assert::assertFalse($item->isHit(), sprintf('Cache key "%s" was found in pool "%s" but should not exist', $key, $poolName));
    }

    /**
     * Gets a value from cache.
     */
    protected function getCachedValue(string $key, string $poolName = 'cache.app')
    {
        $pool = self::getContainer()->get($poolName);

        if (! $pool instanceof AdapterInterface) {
            throw new \RuntimeException("Cache pool {$poolName} is not an instance of AdapterInterface");
        }

        $item = $pool->getItem($key);

        return $item->isHit() ? $item->get() : null;
    }

    /**
     * Sets a value in cache.
     */
    protected function setCachedValue(string $key, $value, int $ttl = 3600, string $poolName = 'cache.app'): void
    {
        $pool = self::getContainer()->get($poolName);

        if (! $pool instanceof AdapterInterface) {
            throw new \RuntimeException("Cache pool {$poolName} is not an instance of AdapterInterface");
        }

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter($ttl);
        $pool->save($item);
    }

    /**
     * Invalidates cache by tag.
     */
    protected function invalidateCacheByTag(string $tag, string $poolName = 'cache.app'): void
    {
        $pool = self::getContainer()->get($poolName);

        if ($pool instanceof TagAwareAdapterInterface) {
            $pool->invalidateTags([$tag]);
        }
    }

    /**
     * Asserts cache contains a value.
     */
    protected function assertCacheContains(string $key, $expectedValue, string $poolName = 'cache.app'): void
    {
        $actualValue = $this->getCachedValue($key, $poolName);
        Assert::assertEquals($expectedValue, $actualValue, sprintf('Cache key "%s" does not contain expected value', $key));
    }

    /**
     * Recursively removes a directory.
     */
    private function recursiveRemoveDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            $path = $directory . '/' . $file;

            if (is_dir($path)) {
                $this->recursiveRemoveDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
