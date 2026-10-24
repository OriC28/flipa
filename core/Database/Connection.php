<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Database\Exceptions\ConnectionException;
use Core\Database\Query\Builder;

class Connection
{
    private ?\PDO $connection = null;
    private ?string $url;
    private string $driver;
    private string $host;
    private string $port;
    private string $database;
    private string $username;
    private string $password;
    private ?array $options;

    public function __construct(private array $config)
    {
        $this->url = $config["url"] ?? null;
        $this->driver = $config["driver"];
        $this->host = $config["host"] ?? '127.0.0.1';
        $this->port = $config["port"] ?? '3306';
        $this->database = $config["database"];
        $this->username = $config["username"] ?? 'root';
        $this->password = $config["password"] ?? '';
        $this->options = $config["options"] ?? $this->getDefaultOptions();
    }
    private function getDefaultOptions(): array
    {
        return [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    private function buildDsn(): string
    {
        if ($this->url !== null) {
            return $this->url;
        }
        $dsn = match ($this->driver) {
            'mysql' => "mysql:host=$this->host;port=$this->port;dbname=$this->database",
            'sqlite' =>  str_starts_with($this->database, '/')
                ? "sqlite:$this->database"
                : "sqlite:/$this->database",
            default => throw new ConnectionException("The driver {$this->driver} is not supported. Try configuring SQLite or MySQL in config/database.php")
        };

        return $dsn;
    }

    public function getPdo(): \PDO
    {
        $dsn = $this->buildDsn();
        if ($this->connection === null) {
            $this->connection = new \PDO($dsn, $this->username, $this->password, $this->options);
            return $this->connection;
        }
        return $this->connection;
    }

    public function table(string $table): Builder
    {
        return (new Builder($this))->table($table);
    }
}
