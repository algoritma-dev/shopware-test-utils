<?php

namespace Algoritma\ShopwareTestUtils\Bootstrap;

use PDO;
use PDOException;
use Shopware\Core\TestBootstrapper;
use Symfony\Component\Dotenv\Dotenv;

class ParallelTestBootstrapper extends TestBootstrapper
{
    private static bool $bootstrapped = false;

    /**
     * @var list<string>
     */
    private array $postInstallCommands = [
        'bin/ci system:install --drop-database --basic-setup --force --no-assign-theme',
        'bin/console dal:refresh:index --only category.indexer --no-interaction',
    ];

    private bool $shouldLoadEnvFile = true;

    public function bootstrap(): TestBootstrapper
    {
        if (self::$bootstrapped) {
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

        $token = getenv('TEST_TOKEN');
        if ($token === false || $token === '') {
            return;
        }

        (new self())->bootstrap();
    }

    /**
     * @param list<string> $commands
     */
    public function setPostInstallCommands(array $commands): self
    {
        $this->postInstallCommands = $commands;

        return $this;
    }

    public function addPostInstallCommand(string $command): self
    {
        $this->postInstallCommands[] = $command;

        return $this;
    }

    public function setLoadEnvFile(bool $loadEnvFile): TestBootstrapper
    {
        $this->shouldLoadEnvFile = $loadEnvFile;

        return parent::setLoadEnvFile($loadEnvFile);
    }

    public function getDatabaseUrl(): string
    {
        $databaseUrl = parent::getDatabaseUrl();
        $token = getenv('TEST_TOKEN');

        if ($token === false || $token === '') {
            return $databaseUrl;
        }

        return $this->appendTokenToDatabaseName($databaseUrl, $token);
    }

    private function prepareParallelDatabase(): void
    {
        $token = getenv('TEST_TOKEN');
        if ($token === false || $token === '') {
            return;
        }

        $this->loadEnvFileIfNeeded();

        $databaseUrl = $this->getDatabaseUrl();
        $this->setDatabaseUrlEnv($databaseUrl);

        $created = $this->ensureParallelDatabaseExists($databaseUrl);
        if ($created) {
            $this->runPostInstallCommands();
        }
    }

    private function loadEnvFileIfNeeded(): void
    {
        if (!$this->shouldLoadEnvFile) {
            return;
        }

        if (!empty($_SERVER['DATABASE_URL']) || !empty($_ENV['DATABASE_URL'])) {
            return;
        }

        if (!class_exists(Dotenv::class)) {
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
        putenv('DATABASE_URL=' . $databaseUrl);
    }

    private function ensureParallelDatabaseExists(string $databaseUrl): bool
    {
        $parts = parse_url($databaseUrl);
        if ($parts === false) {
            return false;
        }

        $dbName = ltrim($parts['path'] ?? '', '/');
        if ($dbName === '') {
            return false;
        }

        $scheme = $parts['scheme'] ?? 'mysql';
        if (!in_array($scheme, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $params = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $params);
        }

        $charset = isset($params['charset']) ? (string) $params['charset'] : 'utf8mb4';

        if ($this->databaseExists($parts, $dbName, $charset, $params)) {
            return false;
        }

        $this->createDatabase($parts, $dbName, $charset, $params);

        return true;
    }

    /**
     * @param array<string, string|int> $parts
     * @param array<string, mixed> $params
     */
    private function databaseExists(array $parts, string $dbName, string $charset, array $params): bool
    {
        $dsn = $this->buildDatabaseDsn($parts, $dbName, $charset, $params);
        if ($dsn === null) {
            return false;
        }

        $user = isset($parts['user']) ? (string) $parts['user'] : '';
        $pass = isset($parts['pass']) ? (string) $parts['pass'] : '';

        try {
            new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            return true;
        } catch (PDOException $exception) {
            if (isset($exception->errorInfo[1]) && (int) $exception->errorInfo[1] === 1049) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, string|int> $parts
     * @param array<string, mixed> $params
     */
    private function createDatabase(array $parts, string $dbName, string $charset, array $params): void
    {
        $dsn = $this->buildServerDsn($parts, $charset, $params);
        if ($dsn === null) {
            return;
        }

        $user = isset($parts['user']) ? (string) $parts['user'] : '';
        $pass = isset($parts['pass']) ? (string) $parts['pass'] : '';

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $escapedDbName = str_replace('`', '``', $dbName);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $escapedDbName . '`');
    }

    private function runPostInstallCommands(): void
    {
        $commands = $this->getPostInstallCommands();
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
    private function getPostInstallCommands(): array
    {
        $commands = $this->postInstallCommands;
        $extra = getenv('SW_TEST_POST_INSTALL_COMMANDS');

        if ($extra === false || $extra === '') {
            return $commands;
        }

        $extraCommands = preg_split('/\R|;/', $extra) ?: [];
        foreach ($extraCommands as $command) {
            $command = trim($command);
            if ($command !== '') {
                $commands[] = $command;
            }
        }

        return $commands;
    }

    private function runCommand(string $command): void
    {
        $descriptorSpec = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $this->getProjectDir());
        if (!\is_resource($process)) {
            throw new \RuntimeException('Failed to start command: ' . $command);
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            throw new \RuntimeException(sprintf(
                "Command failed with exit code %d: %s\n%s%s",
                $exitCode,
                $command,
                $stdout ? "STDOUT:\n" . $stdout . "\n" : '',
                $stderr ? "STDERR:\n" . $stderr : ''
            ));
        }
    }

    /**
     * @param array<string, string|int> $parts
     * @param array<string, mixed> $params
     */
    private function buildServerDsn(array $parts, string $charset, array $params): ?string
    {
        if (isset($params['unix_socket'])) {
            return 'mysql:unix_socket=' . $params['unix_socket'] . ';charset=' . $charset;
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
        if ($charset !== '') {
            $dsn .= ';charset=' . $charset;
        }

        return $dsn;
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

    private function appendTokenToDatabaseName(string $databaseUrl, string $token): string
    {
        $parts = parse_url($databaseUrl);
        if ($parts === false) {
            return $databaseUrl;
        }

        $path = $parts['path'] ?? '';
        if ($path === '') {
            return $databaseUrl;
        }

        $databaseName = ltrim($path, '/');
        if ($databaseName === '') {
            return $databaseUrl;
        }

        $token = preg_replace('/[^A-Za-z0-9_]+/', '_', $token) ?? '';
        $token = trim($token, '_');
        if ($token === '') {
            $token = 'worker';
        }

        $parts['path'] = '/' . $databaseName . '_p' . $token;

        return $this->buildDatabaseUrl($parts);
    }

    /**
     * @param array<string, string|int> $parts
     */
    private function buildDatabaseUrl(array $parts): string
    {
        $auth = '';
        if (isset($parts['user'])) {
            $auth = $parts['user'];
            if (isset($parts['pass'])) {
                $auth .= ':' . $parts['pass'];
            }
            $auth .= '@';
        }

        return \sprintf(
            '%s://%s%s%s%s%s',
            $parts['scheme'] ?? 'mysql',
            $auth,
            $parts['host'] ?? 'localhost',
            isset($parts['port']) ? ':' . $parts['port'] : '',
            $parts['path'] ?? '',
            isset($parts['query']) ? '?' . $parts['query'] : ''
        );
    }
}
