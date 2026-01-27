<?php

namespace Algoritma\ShopwareTestUtils\Bootstrap;

use Shopware\Core\TestBootstrapper;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;
use function dump;

class ParallelTestBootstrapper extends TestBootstrapper
{
    private static bool $bootstrapped = false;
    private ?string $parallelDatabaseUrl = null;

    /**
     * @var list<string>
     */
    private array $installCommands = [
        'bin/console system:install --drop-database --basic-setup --force --no-assign-theme',
    ];

    private bool $shouldLoadEnvFile = true;

    public function bootstrap(): TestBootstrapper
    {
        if (self::$bootstrapped) {
            return $this;
        }

        if ($this->shouldSkipBootstrapForParatestMaster()) {
            $classLoader = $this->getClassLoader();
            KernelLifecycleManager::prepare($classLoader);

            return $this;
        }

        self::$bootstrapped = true;
        $this->prepareParallelDatabase();

        return parent::bootstrap();
    }

    public static function ensureParallelBootstrap(): void
    {
        if (self::$bootstrapped) {
            return;
        }

        $token = \getenv('TEST_TOKEN');
        if ($token === false || $token === '') {
            return;
        }

        (new self())->bootstrap();
    }

    /**
     * @param list<string> $commands
     */
    public function setInstallCommands(array $commands): self
    {
        $this->installCommands = $commands;

        return $this;
    }

    public function addInstallCommand(string $command): self
    {
        $this->installCommands[] = $command;

        return $this;
    }

    public function setLoadEnvFile(bool $loadEnvFile): TestBootstrapper
    {
        $this->shouldLoadEnvFile = $loadEnvFile;

        return parent::setLoadEnvFile($loadEnvFile);
    }

    public function getDatabaseUrl(): string
    {
        if ($this->parallelDatabaseUrl !== null) {
            return $this->parallelDatabaseUrl;
        }

        $rawUrl = $this->getRawDatabaseUrl();
        $parts = \parse_url($rawUrl) ?: [];

        $dbName = \ltrim((string) ($parts['path'] ?? ''), '/');
        if ($dbName === '') {
            $dbName = 'root';
        }

        $dbName = $this->ensureTestDatabaseName($dbName);
        $token = \getenv('TEST_TOKEN');
        if ($token !== false && $token !== '') {
            $dbName = $this->ensureTokenSuffix($dbName, (string) $token);
        }

        $parts['path'] = '/' . $dbName;
        $this->parallelDatabaseUrl = $this->buildDatabaseUrl($parts);

        return $this->parallelDatabaseUrl;
    }

    private function prepareParallelDatabase(): void
    {
        $token = \getenv('TEST_TOKEN');
        if ($token === false || $token === '') {
            return;
        }

        $this->loadEnvFileIfNeeded();
        $this->ensureCacheIdEnv();
        $this->ensureCacheDirEnv();
        $this->ensureRedisPrefixEnv();

        $databaseUrl = $this->getDatabaseUrl();
        $this->setDatabaseUrlEnv($databaseUrl);

        if ($this->databaseExists($databaseUrl)) {
            return;
        }

        $this->runInstallCommands();
    }

    private function loadEnvFileIfNeeded(): void
    {
        if (! $this->shouldLoadEnvFile) {
            return;
        }

        if (! empty($_SERVER['DATABASE_URL']) || ! empty($_ENV['DATABASE_URL'])) {
            return;
        }

        if (! \class_exists(Dotenv::class)) {
            return;
        }

        $envFilePath = $this->getProjectDir() . '/.env';
        if (\is_file($envFilePath) || \is_file($envFilePath . '.dist') || \is_file($envFilePath . '.local.php')) {
            (new Dotenv())->usePutenv()->bootEnv($envFilePath);
        }
    }

    private function setDatabaseUrlEnv(string $databaseUrl): void
    {
        $_SERVER['DATABASE_URL'] = $databaseUrl;
        $_ENV['DATABASE_URL'] = $databaseUrl;
        \putenv('DATABASE_URL=' . $databaseUrl);
    }

    private function ensureCacheIdEnv(): void
    {
        if (! empty($_SERVER['SHOPWARE_CACHE_ID']) || ! empty($_ENV['SHOPWARE_CACHE_ID'])) {
            return;
        }

        $env = \getenv('SHOPWARE_CACHE_ID');
        if ($env !== false && $env !== '') {
            return;
        }

        $token = \getenv('UNIQUE_TEST_TOKEN');
        if ($token === false || $token === '') {
            $token = \getenv('TEST_TOKEN');
        }

        if ($token === false || $token === '') {
            return;
        }

        $this->setEnvVar('SHOPWARE_CACHE_ID', 'test_' . $token);
    }

    private function ensureCacheDirEnv(): void
    {
        if (! empty($_SERVER['APP_CACHE_DIR']) || ! empty($_ENV['APP_CACHE_DIR'])) {
            return;
        }

        $env = \getenv('APP_CACHE_DIR');
        if ($env !== false && $env !== '') {
            return;
        }

        $token = \getenv('UNIQUE_TEST_TOKEN');
        if ($token === false || $token === '') {
            $token = \getenv('TEST_TOKEN');
        }

        if ($token === false || $token === '') {
            return;
        }

        $baseDir = \rtrim(\sys_get_temp_dir(), \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . 'shopware-cache-' . $token;
        $this->setEnvVar('APP_CACHE_DIR', $baseDir);

        $cacheRoot = $baseDir . \DIRECTORY_SEPARATOR . 'var' . \DIRECTORY_SEPARATOR . 'cache';
        if (!\is_dir($cacheRoot) && (!mkdir($cacheRoot, 0777, true) && !is_dir($cacheRoot))) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $cacheRoot));
        }
    }

    private function ensureRedisPrefixEnv(): void
    {
        if (! empty($_SERVER['REDIS_PREFIX']) || ! empty($_ENV['REDIS_PREFIX'])) {
            return;
        }

        $env = \getenv('REDIS_PREFIX');
        if ($env !== false && $env !== '') {
            return;
        }

        $token = \getenv('UNIQUE_TEST_TOKEN');
        if ($token === false || $token === '') {
            $token = \getenv('TEST_TOKEN');
        }

        if ($token === false || $token === '') {
            return;
        }

        $this->setEnvVar('REDIS_PREFIX', 'test_' . $token . '_');
    }

    private function setEnvVar(string $key, string $value): void
    {
        $_SERVER[$key] = $value;
        $_ENV[$key] = $value;
        \putenv($key . '=' . $value);
    }

    private function databaseExists(string $databaseUrl): bool
    {
        $parts = \parse_url($databaseUrl);

        if ($parts === false) {
            return false;
        }

        $dbName = \ltrim($parts['path'] ?? '', '/');
        if ($dbName === '') {
            return false;
        }

        $scheme = $parts['scheme'] ?? 'mysql';
        if (! \in_array($scheme, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $params = [];
        if (isset($parts['query'])) {
            \parse_str($parts['query'], $params);
        }

        $charset = isset($params['charset']) ? (string) $params['charset'] : 'utf8mb4';

        $dsn = $this->buildDatabaseDsn($parts, $dbName, $charset, $params);
        if ($dsn === null) {
            return false;
        }

        $user = $parts['user'] ?? '';
        $pass = $parts['pass'] ?? '';

        try {
            new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            return true;
        } catch (\PDOException $exception) {
            if (isset($exception->errorInfo[1]) && (int) $exception->errorInfo[1] === 1049) {
                return false;
            }

            throw $exception;
        }
    }

    private function runInstallCommands(): void
    {
        $commands = $this->getInstallCommands();
        if ($commands === []) {
            return;
        }

        foreach ($commands as $command) {
            $this->runCommand($command);
        }
    }

    /**
     * @return list<string>
     */
    private function getInstallCommands(): array
    {
        $commands = $this->installCommands;
        $extra = \getenv('SW_TEST_INSTALL_COMMANDS');

        if ($extra === false || $extra === '') {
            return $commands;
        }

        $extraCommands = \preg_split('/\R|;/', $extra) ?: [];
        foreach ($extraCommands as $command) {
            $command = \trim($command);
            if ($command !== '') {
                $commands[] = $command;
            }
        }

        return $commands;
    }

    private function shouldSkipBootstrapForParatestMaster(): bool
    {
        $token = \getenv('TEST_TOKEN');
        if ($token !== false && $token !== '') {
            return false;
        }

        $paratest = \getenv('PARATEST');
        if ($paratest !== false && $paratest !== '') {
            return true;
        }

        $argv = $_SERVER['argv'] ?? [];
        foreach ($argv as $arg) {
            if (\stripos((string) $arg, 'paratest') !== false) {
                return true;
            }
        }

        return false;
    }

    private function runCommand(string $command): void
    {
        $this->disableExecutionTimeout();

        $process = Process::fromShellCommandline($command, $this->getProjectDir());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $exitCode = $process->run();

        if (! $process->isSuccessful()) {
            $stdout = $process->getOutput();
            $stderr = $process->getErrorOutput();
            throw new \RuntimeException(\sprintf("Command failed with exit code %d: %s\n%s%s", $exitCode, $command, $stdout !== '' && $stdout !== '0' ? "STDOUT:\n" . $stdout . "\n" : '', $stderr !== '' && $stderr !== '0' ? "STDERR:\n" . $stderr : ''));
        }
    }

    private function disableExecutionTimeout(): void
    {
        if (\function_exists('set_time_limit')) {
            @\set_time_limit(0);
        }

        if (\function_exists('ini_set')) {
            @\ini_set('max_execution_time', '0');
        }
    }

    /**
     * @param array<string, string|int> $parts
     * @param array<string, mixed> $params
     */
    private function buildDatabaseDsn(array $parts, string $dbName, string $charset, array $params): ?string
    {
        if (isset($params['unix_socket'])) {
            return 'mysql:unix_socket=' . $params['unix_socket'] . ';dbname=' . $dbName . ';charset=' . $charset;
        }

        $host = $parts['host'] ?? 'localhost';
        $port = isset($parts['port']) ? (string) $parts['port'] : '';
        if ($host === '') {
            return null;
        }

        $dsn = 'mysql:host=' . $host;
        if ($port !== '') {
            $dsn .= ';port=' . $port;
        }
        $dsn .= ';dbname=' . $dbName;
        if ($charset !== '') {
            $dsn .= ';charset=' . $charset;
        }

        return $dsn;
    }

    private function getRawDatabaseUrl(): string
    {
        if (! empty($_SERVER['DATABASE_URL'])) {
            return (string) $_SERVER['DATABASE_URL'];
        }

        if (! empty($_ENV['DATABASE_URL'])) {
            return (string) $_ENV['DATABASE_URL'];
        }

        $env = \getenv('DATABASE_URL');
        if ($env !== false && $env !== '') {
            return (string) $env;
        }

        return '';
    }

    private function ensureTestDatabaseName(string $dbName): string
    {
        if (\preg_match('/_test(?:_p?[A-Za-z0-9]+)?$/', $dbName) === 1) {
            return $dbName;
        }

        return $dbName . '_test';
    }

    private function ensureTokenSuffix(string $dbName, string $token): string
    {
        $escapedToken = \preg_quote($token, '/');
        if (\preg_match('/(_p)?' . $escapedToken . '$/', $dbName) === 1) {
            return $dbName;
        }

        return $dbName . '_p' . $token;
    }

    /**
     * @param array<string, string|int> $parts
     */
    private function buildDatabaseUrl(array $parts): string
    {
        $scheme = $parts['scheme'] ?? 'mysql';
        $host = $parts['host'] ?? 'localhost';
        $port = isset($parts['port']) ? (':' . $parts['port']) : '';
        $path = $parts['path'] ?? '';

        $auth = '';
        if (! empty($parts['user'])) {
            $auth = (string) $parts['user'];
            if (isset($parts['pass'])) {
                $auth .= ':' . $parts['pass'];
            }
            $auth .= '@';
        }

        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';

        return \sprintf('%s://%s%s%s%s%s', $scheme, $auth, $host, $port, $path, $query);
    }
}
