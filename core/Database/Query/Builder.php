<?php

namespace Core\Database\Query;

use Core\Database\Connection;
use Core\Database\Exceptions\DatabaseException;
use Core\Database\Exceptions\QueryException;

class Builder
{
    private string $table;
    private array $columns = [];
    private array $orders = [];
    private array $conditions = [];
    private array $bindings = [];
    public function __construct(private Connection $connection) {}


    protected function run(string $sql, array $bindings): \PDOStatement
    {
        try {
            $conn = $this->connection->getPdo();
            $stmt = $conn->prepare($sql);
            $stmt->execute($bindings);
            return $stmt;
        } catch (\PDOException $e) {
            throw new QueryException($e->getMessage(), $sql, $bindings);
        }
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = "$column $direction";
        return $this;
    }

    public function where(string $column, mixed $value): self
    {
        $this->conditions[] = "$column = ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function first(): ?array
    {
        return $this->get()[0] ?? null;
    }

    public function find(mixed $id): ?array
    {
        return $this->where('id', $id)->first();
    }

    public function count()
    {
        $temp = clone $this;
        $temp->columns = ['COUNT(*) as aggregate'];
        return $temp->first()['aggregate'];
    }

    public function get(): array
    {
        $conditions = implode(' AND ', $this->conditions);
        $columns = $this->columns ? implode(', ', $this->columns) : '*';

        $sql = $this->conditions
            ? "SELECT {$columns} FROM {$this->table} WHERE {$conditions}"
            : "SELECT {$columns} FROM {$this->table}";

        $sql .= $this->orders ? " ORDER BY " . implode(', ', $this->orders) : '';

        $stmt = $this->run($sql, $this->bindings);

        return $stmt->fetchAll();
    }
    private function buildInsertQuery(array $data): string
    {
        $columns = implode(', ', array_keys($data));
        $markers = implode(', ', array_fill(0, \count($data), '?'));

        return  "INSERT INTO {$this->table} ({$columns}) VALUES ({$markers})";
    }

    public function insert(array $data): int
    {
        $values = array_values($data);

        $sql = $this->buildInsertQuery($data);

        $stmt = $this->run($sql, $values);

        return $stmt->rowCount();
    }
    public function insertGetId(array $data): ?int
    {
        $values = array_values($data);

        $sql = $this->buildInsertQuery($data);

        $this->run($sql, $values);

        return (int) $this->connection->getPdo()->lastInsertId();
    }

    public function update(array $data): int
    {
        if (empty($this->conditions)) {
            throw new DatabaseException("Cannot update without conditions.");
        }

        $setClauses = [];
        $setBindings = [];

        foreach ($data as $key => $value) {
            $setClauses[] = "$key = ?";
            $setBindings[] = $value;
        }

        $columns = implode(', ', $setClauses);
        $conditions = implode(' AND ', $this->conditions);
        $values = [...$setBindings, ...$this->bindings];

        $sql = "UPDATE {$this->table} SET {$columns} WHERE {$conditions}";

        $stmt = $this->run($sql, $values);

        return $stmt->rowCount();
    }

    public function delete(): int
    {
        if (empty($this->conditions)) {
            throw new DatabaseException("Cannot delete without conditions.");
        }
        $conditions = implode(' AND ', $this->conditions);

        $sql = "DELETE FROM {$this->table} WHERE {$conditions}";

        $stmt = $this->run($sql, $this->bindings);

        return $stmt->rowCount();
    }
}
