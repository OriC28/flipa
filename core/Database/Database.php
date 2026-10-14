<?php

use Core\Database\Connection;

class Database
{
    private array $connections = [];
    public function __construct(private Config $config) {}

    public function connection(?string $name = null): Connection
    {
        $driver = $name ?? $this->config->get("database.default");
        $configConnection = $this->config->get("database.connections.$driver");
        if (!isset($this->connections[$driver])) {
            $newConnection = new Connection($configConnection);
            $this->connections[$driver] =  $newConnection;
            return $newConnection;
        }
        return $this->connections[$driver];
    }
}
