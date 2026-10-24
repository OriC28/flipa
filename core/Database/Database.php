<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Config\Config;
use Core\Database\Connection;
use Core\Database\Exceptions\DatabaseConfigException;

class Database
{
    private array $connections = [];
    public function __construct(private Config $config) {}

    public function connection(?string $name = null): Connection
    {
        $driver = $name ?? $this->config->get("database.default");
        $configConnection = $this->config->get("database.connections.$driver");
        if ($configConnection === null) {
            throw new DatabaseConfigException("Cannot connect to the database. Driver $driver is not supported.");
        }

        if (!isset($this->connections[$driver])) {
            $newConnection = new Connection($configConnection);
            $this->connections[$driver] =  $newConnection;
            return $newConnection;
        }
        return $this->connections[$driver];
    }
}
