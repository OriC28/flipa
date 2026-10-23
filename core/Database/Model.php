<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Database\Query\Builder;
use JsonSerializable;

class Model implements JsonSerializable
{
    protected string $table = "";
    protected array $attributes = [];
    protected Connection $connection;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $db = app(Database::class);
        $this->connection = $db->connection();
        $this->table = $this->tableNameAutoDiscovery();
    }

    public function jsonSerialize(): array
    {
        return $this->attributes;
    }

    public function __debugInfo()
    {
        return $this->attributes;
    }

    public function __get(string $name): mixed
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        return null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    private function tableNameAutoDiscovery(): string
    {
        $basenameClass = class_basename($this);
        $names = preg_split('/(?=[A-Z])/', $basenameClass, -1, PREG_SPLIT_NO_EMPTY);
        return strtolower(implode('_', $names) . 's');
    }

    private function newQuery(): Builder
    {
        return (new Builder($this->connection))->table($this->table);
    }

    private function hydrate(array|object $row): static
    {
        $model = new static();
        foreach ($row as $key => $value) {
            $model->attributes[$key] = $value;
        }
        return $model;
    }

    public static function all(): array
    {
        $model = new static();
        $rows = $model->newQuery()->get();
        $instances = array_map(
            fn($row) => $model->hydrate($row),
            $rows
        );

        return $instances;
    }

    public static function find(mixed $id): static|null
    {
        $model = new static();
        $row = $model->newQuery()->find($id);
        if ($row === null) return null;
        return $model->hydrate($row);
    }
    public function save(): void
    {
        if (isset($this->attributes[$this->primaryKey])) {
            $id = $this->attributes[$this->primaryKey];
            $this->newQuery()->where($this->primaryKey, $id)->update($this->attributes);
            return;
        }
        $newId = $this->newQuery()->insertGetId($this->attributes);
        $this->attributes[$this->primaryKey] = $newId;
    }

    public function delete(): void
    {
        if (isset($this->attributes[$this->primaryKey])) {
            $id = $this->attributes[$this->primaryKey];
            $this->newQuery()->where($this->primaryKey, $id)->delete();

            $this->attributes[$this->primaryKey] = null;
        }
    }
}
