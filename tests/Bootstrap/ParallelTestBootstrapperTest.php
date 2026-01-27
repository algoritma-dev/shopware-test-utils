<?php

namespace Algoritma\ShopwareTestUtils\Tests\Bootstrap;

use Algoritma\ShopwareTestUtils\Bootstrap\ParallelTestBootstrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParallelTestBootstrapper::class)]
class ParallelTestBootstrapperTest extends TestCase
{
    /**
     * @var array<string, array{getenv: string|false, env: string|null, env_set: bool, server: string|null, server_set: bool}>
     */
    private array $envBackup = [];

    private bool $bootstrappedBackup = false;

    /**
     * @var array<int, string>|null
     */
    private ?array $argvBackup = null;

    protected function setUp(): void
    {
        $this->bootstrappedBackup = $this->getBootstrappedFlag();
        $this->setBootstrappedFlag(false);

        $this->backupEnvVar('TEST_TOKEN');
        $this->backupEnvVar('UNIQUE_TEST_TOKEN');
        $this->backupEnvVar('PARATEST');
        $this->backupEnvVar('SW_TEST_INSTALL_COMMANDS');
        $this->backupEnvVar('DATABASE_URL');
        $this->backupEnvVar('SHOPWARE_CACHE_ID');
        $this->backupEnvVar('APP_CACHE_DIR');
        $this->backupEnvVar('REDIS_PREFIX');

        $this->argvBackup = $_SERVER['argv'] ?? null;
    }

    protected function tearDown(): void
    {
        $this->restoreEnvVars();

        if ($this->argvBackup === null) {
            unset($_SERVER['argv']);
        } else {
            $_SERVER['argv'] = $this->argvBackup;
        }

        $this->setBootstrappedFlag($this->bootstrappedBackup);
    }

    public function testShouldSkipBootstrapForParatestMasterWhenParatestEnvSet(): void
    {
        $this->setEnvVar('PARATEST', '1');
        $this->unsetEnvVar('TEST_TOKEN');
        $_SERVER['argv'] = ['phpunit'];

        $bootstrapper = new ParallelTestBootstrapper();
        $shouldSkip = $this->callPrivateMethod($bootstrapper, 'shouldSkipBootstrapForParatestMaster');

        $this->assertTrue($shouldSkip);
    }

    public function testShouldSkipBootstrapForParatestMasterWhenArgvContainsParatest(): void
    {
        $this->unsetEnvVar('PARATEST');
        $this->unsetEnvVar('TEST_TOKEN');
        $_SERVER['argv'] = ['phpunit', '--runner', 'paratest'];

        $bootstrapper = new ParallelTestBootstrapper();
        $shouldSkip = $this->callPrivateMethod($bootstrapper, 'shouldSkipBootstrapForParatestMaster');

        $this->assertTrue($shouldSkip);
    }

    public function testShouldNotSkipBootstrapWhenTestTokenPresent(): void
    {
        $this->setEnvVar('TEST_TOKEN', '1');
        $this->setEnvVar('PARATEST', '1');
        $_SERVER['argv'] = ['paratest'];

        $bootstrapper = new ParallelTestBootstrapper();
        $shouldSkip = $this->callPrivateMethod($bootstrapper, 'shouldSkipBootstrapForParatestMaster');

        $this->assertFalse($shouldSkip);
    }

    public function testSetInstallCommandsOverridesDefaults(): void
    {
        $bootstrapper = new ParallelTestBootstrapper();
        $bootstrapper->setInstallCommands(['command:one']);
        $this->unsetEnvVar('SW_TEST_INSTALL_COMMANDS');

        $commands = $this->callPrivateMethod($bootstrapper, 'getInstallCommands');

        $this->assertSame(['command:one'], $commands);
    }

    public function testAddInstallCommandAppends(): void
    {
        $bootstrapper = new ParallelTestBootstrapper();
        $bootstrapper->setInstallCommands(['command:one'])
            ->addInstallCommand('command:two');
        $this->unsetEnvVar('SW_TEST_INSTALL_COMMANDS');

        $commands = $this->callPrivateMethod($bootstrapper, 'getInstallCommands');

        $this->assertSame(['command:one', 'command:two'], $commands);
    }

    public function testGetInstallCommandsMergesEnvCommands(): void
    {
        $bootstrapper = new ParallelTestBootstrapper();
        $bootstrapper->setInstallCommands(['command:one']);
        $this->setEnvVar('SW_TEST_INSTALL_COMMANDS', "command:two\ncommand:three; command:four;;");

        $commands = $this->callPrivateMethod($bootstrapper, 'getInstallCommands');

        $this->assertSame(['command:one', 'command:two', 'command:three', 'command:four'], $commands);
    }

    public function testSetLoadEnvFileUpdatesFlag(): void
    {
        $bootstrapper = new ParallelTestBootstrapper();
        $result = $bootstrapper->setLoadEnvFile(false);

        $this->assertSame($bootstrapper, $result);
        $this->assertFalse($this->getPrivateProperty($bootstrapper, ParallelTestBootstrapper::class, 'shouldLoadEnvFile'));
    }

    public function testBuildDatabaseDsnUsesUnixSocket(): void
    {
        $bootstrapper = new ParallelTestBootstrapper();
        $dsn = $this->callPrivateMethod($bootstrapper, 'buildDatabaseDsn', [
            ['host' => 'localhost'],
            'shopware_test',
            'utf8mb4',
            ['unix_socket' => '/tmp/mysql.sock'],
        ]);

        $this->assertSame('mysql:unix_socket=/tmp/mysql.sock;dbname=shopware_test;charset=utf8mb4', $dsn);
    }

    public function testBuildDatabaseDsnUsesHostAndPort(): void
    {
        $bootstrapper = new ParallelTestBootstrapper();
        $dsn = $this->callPrivateMethod($bootstrapper, 'buildDatabaseDsn', [
            ['host' => 'db', 'port' => 3307],
            'shopware_test',
            'utf8mb4',
            [],
        ]);

        $this->assertSame('mysql:host=db;port=3307;dbname=shopware_test;charset=utf8mb4', $dsn);
    }

    public function testBuildDatabaseDsnReturnsNullWhenHostMissing(): void
    {
        $bootstrapper = new ParallelTestBootstrapper();
        $dsn = $this->callPrivateMethod($bootstrapper, 'buildDatabaseDsn', [
            ['host' => ''],
            'shopware_test',
            'utf8mb4',
            [],
        ]);

        $this->assertNull($dsn);
    }

    public function testDatabaseExistsReturnsFalseForUnsupportedScheme(): void
    {
        $bootstrapper = new ParallelTestBootstrapper();
        $exists = $this->callPrivateMethod($bootstrapper, 'databaseExists', ['sqlite:///:memory:']);

        $this->assertFalse($exists);
    }

    public function testSetDatabaseUrlEnvSetsServerEnvAndPutenv(): void
    {
        $bootstrapper = new ParallelTestBootstrapper();
        $databaseUrl = 'mysql://user:pass@localhost/shopware_test';

        $this->callPrivateMethod($bootstrapper, 'setDatabaseUrlEnv', [$databaseUrl]);

        $this->assertSame($databaseUrl, $_SERVER['DATABASE_URL']);
        $this->assertSame($databaseUrl, $_ENV['DATABASE_URL']);
        $this->assertSame($databaseUrl, getenv('DATABASE_URL'));
    }

    public function testGetDatabaseUrlAppendsTestAndTokenSuffix(): void
    {
        $this->setEnvVar('DATABASE_URL', 'mysql://user:pass@localhost/fintyre_b2b_2024');
        $this->setEnvVar('TEST_TOKEN', '2');

        $bootstrapper = new ParallelTestBootstrapper();
        $databaseUrl = $bootstrapper->getDatabaseUrl();

        $this->assertSame('mysql://user:pass@localhost/fintyre_b2b_2024_test_p2', $databaseUrl);
    }

    public function testGetDatabaseUrlKeepsExistingTokenSuffix(): void
    {
        $this->setEnvVar('DATABASE_URL', 'mysql://user:pass@localhost/fintyre_b2b_2024_test2');
        $this->setEnvVar('TEST_TOKEN', '2');

        $bootstrapper = new ParallelTestBootstrapper();
        $databaseUrl = $bootstrapper->getDatabaseUrl();

        $this->assertSame('mysql://user:pass@localhost/fintyre_b2b_2024_test2_test_p2', $databaseUrl);
    }

    public function testGetDatabaseUrlAppendsTestSuffixWhenTokenMissing(): void
    {
        $this->setEnvVar('DATABASE_URL', 'mysql://user:pass@localhost/fintyre_b2b_2024');
        $this->unsetEnvVar('TEST_TOKEN');

        $bootstrapper = new ParallelTestBootstrapper();
        $databaseUrl = $bootstrapper->getDatabaseUrl();

        $this->assertSame('mysql://user:pass@localhost/fintyre_b2b_2024_test', $databaseUrl);
    }

    public function testEnsureCacheIdEnvUsesUniqueToken(): void
    {
        $this->setEnvVar('UNIQUE_TEST_TOKEN', '2_697894cfcf70c');
        $this->unsetEnvVar('SHOPWARE_CACHE_ID');

        $bootstrapper = new ParallelTestBootstrapper();
        $this->callPrivateMethod($bootstrapper, 'ensureCacheIdEnv');

        $this->assertSame('test_2_697894cfcf70c', $_SERVER['SHOPWARE_CACHE_ID']);
        $this->assertSame('test_2_697894cfcf70c', $_ENV['SHOPWARE_CACHE_ID']);
        $this->assertSame('test_2_697894cfcf70c', getenv('SHOPWARE_CACHE_ID'));
    }

    public function testEnsureCacheIdEnvDoesNotOverrideExisting(): void
    {
        $this->setEnvVar('SHOPWARE_CACHE_ID', 'custom_cache');
        $this->setEnvVar('UNIQUE_TEST_TOKEN', '2_697894cfcf70c');

        $bootstrapper = new ParallelTestBootstrapper();
        $this->callPrivateMethod($bootstrapper, 'ensureCacheIdEnv');

        $this->assertSame('custom_cache', $_SERVER['SHOPWARE_CACHE_ID']);
        $this->assertSame('custom_cache', $_ENV['SHOPWARE_CACHE_ID']);
        $this->assertSame('custom_cache', getenv('SHOPWARE_CACHE_ID'));
    }

    public function testEnsureCacheDirEnvUsesUniqueToken(): void
    {
        $this->setEnvVar('UNIQUE_TEST_TOKEN', '2_697894cfcf70c');
        $this->unsetEnvVar('APP_CACHE_DIR');

        $bootstrapper = new ParallelTestBootstrapper();
        $this->callPrivateMethod($bootstrapper, 'ensureCacheDirEnv');

        $expected = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'shopware-cache-2_697894cfcf70c';

        $this->assertSame($expected, $_SERVER['APP_CACHE_DIR']);
        $this->assertSame($expected, $_ENV['APP_CACHE_DIR']);
        $this->assertSame($expected, getenv('APP_CACHE_DIR'));
    }

    public function testEnsureCacheDirEnvDoesNotOverrideExisting(): void
    {
        $this->setEnvVar('APP_CACHE_DIR', '/tmp/custom-cache');
        $this->setEnvVar('UNIQUE_TEST_TOKEN', '2_697894cfcf70c');

        $bootstrapper = new ParallelTestBootstrapper();
        $this->callPrivateMethod($bootstrapper, 'ensureCacheDirEnv');

        $this->assertSame('/tmp/custom-cache', $_SERVER['APP_CACHE_DIR']);
        $this->assertSame('/tmp/custom-cache', $_ENV['APP_CACHE_DIR']);
        $this->assertSame('/tmp/custom-cache', getenv('APP_CACHE_DIR'));
    }

    public function testEnsureRedisPrefixEnvUsesUniqueToken(): void
    {
        $this->setEnvVar('UNIQUE_TEST_TOKEN', '2_697894cfcf70c');
        $this->unsetEnvVar('REDIS_PREFIX');

        $bootstrapper = new ParallelTestBootstrapper();
        $this->callPrivateMethod($bootstrapper, 'ensureRedisPrefixEnv');

        $this->assertSame('test_2_697894cfcf70c_', $_SERVER['REDIS_PREFIX']);
        $this->assertSame('test_2_697894cfcf70c_', $_ENV['REDIS_PREFIX']);
        $this->assertSame('test_2_697894cfcf70c_', getenv('REDIS_PREFIX'));
    }

    public function testEnsureRedisPrefixEnvDoesNotOverrideExisting(): void
    {
        $this->setEnvVar('REDIS_PREFIX', 'custom_prefix_');
        $this->setEnvVar('UNIQUE_TEST_TOKEN', '2_697894cfcf70c');

        $bootstrapper = new ParallelTestBootstrapper();
        $this->callPrivateMethod($bootstrapper, 'ensureRedisPrefixEnv');

        $this->assertSame('custom_prefix_', $_SERVER['REDIS_PREFIX']);
        $this->assertSame('custom_prefix_', $_ENV['REDIS_PREFIX']);
        $this->assertSame('custom_prefix_', getenv('REDIS_PREFIX'));
    }

    /**
     * @param array<int, mixed> $args
     */
    private function callPrivateMethod(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $reflectionMethod = $reflection->getMethod($method);

        return $reflectionMethod->invokeArgs($object, $args);
    }

    private function getPrivateProperty(object $object, string $class, string $property): mixed
    {
        $reflection = new \ReflectionClass($class);
        $reflectionProperty = $reflection->getProperty($property);

        return $reflectionProperty->getValue($object);
    }

    private function getBootstrappedFlag(): bool
    {
        $reflection = new \ReflectionClass(ParallelTestBootstrapper::class);
        $reflectionProperty = $reflection->getProperty('bootstrapped');

        return (bool) $reflectionProperty->getValue();
    }

    private function setBootstrappedFlag(bool $value): void
    {
        $reflection = new \ReflectionClass(ParallelTestBootstrapper::class);
        $reflectionProperty = $reflection->getProperty('bootstrapped');
        $reflectionProperty->setValue(null, $value);
    }

    private function backupEnvVar(string $key): void
    {
        if (array_key_exists($key, $this->envBackup)) {
            return;
        }

        $this->envBackup[$key] = [
            'getenv' => getenv($key),
            'env' => array_key_exists($key, $_ENV) ? (string) $_ENV[$key] : null,
            'env_set' => array_key_exists($key, $_ENV),
            'server' => array_key_exists($key, $_SERVER) ? (string) $_SERVER[$key] : null,
            'server_set' => array_key_exists($key, $_SERVER),
        ];
    }

    private function setEnvVar(string $key, string $value): void
    {
        $this->backupEnvVar($key);

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }

    private function unsetEnvVar(string $key): void
    {
        $this->backupEnvVar($key);

        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }

    private function restoreEnvVars(): void
    {
        foreach ($this->envBackup as $key => $state) {
            if ($state['env_set']) {
                $_ENV[$key] = $state['env'];
            } else {
                unset($_ENV[$key]);
            }

            if ($state['server_set']) {
                $_SERVER[$key] = $state['server'];
            } else {
                unset($_SERVER[$key]);
            }

            if ($state['getenv'] !== false) {
                putenv($key . '=' . $state['getenv']);
            } else {
                putenv($key);
            }
        }
    }
}
