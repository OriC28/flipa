<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Database\Query\Builder;
use Core\Events\Dispatcher;
use JsonSerializable;
use Core\Events\EventStates\Created;
use Core\Events\EventStates\Creating;
use Core\Events\EventStates\Updating;
use Core\Events\EventStates\Updated;
use Core\Events\EventStates\Deleted;
use Core\Events\EventStates\Deleting;

class Model implements JsonSerializable
{
    protected string $table = "";
    protected array $attributes = [];
    protected Connection $connection;
    protected string $primaryKey = 'id';
    protected array $fillables = [];
    protected array $hidden = [];
    private Dispatcher $dispatcher;

    public function __construct()
    {
        $db = app(Database::class);
        $this->connection = $db->connection();
        $this->table = $this->tableNameAutoDiscovery();
        $this->dispatcher = app(Dispatcher::class);;
    }

    public function jsonSerialize(): array
    {
        return $this->getVisibleAttributes();
    }

    public function __debugInfo(): array
    {
        return $this->getVisibleAttributes();
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

    private function getVisibleAttributes(): array
    {
        $attributesFiltered = [];
        foreach ($this->attributes as $key => $value) {
            if (!\in_array($key, $this->hidden)) {
                $attributesFiltered[$key] = $value;
            }
        }
        return $attributesFiltered;
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
    private function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            if (\in_array($key, $this->fillables)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
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
            if (!$this->dispatcher->dispatch(new Updating($this))) return;

            $id = $this->attributes[$this->primaryKey];
            $this->newQuery()->where($this->primaryKey, $id)->update($this->attributes);

            $this->dispatcher->dispatch(new Updated($this));
            return;
        }
        if (!$this->dispatcher->dispatch(new Creating($this))) return;

        $newId = $this->newQuery()->insertGetId($this->attributes);
        $this->attributes[$this->primaryKey] = $newId;

        $this->dispatcher->dispatch(new Created($this));
    }

    public static function create(array $data): static
    {
        $model = new static();
        $model->fill($data);
        $model->save();
        return $model;
    }

    public function delete(): void
    {
        if (isset($this->attributes[$this->primaryKey])) {
            if (!$this->dispatcher->dispatch(new Deleting($this))) return;

            $id = $this->attributes[$this->primaryKey];
            $this->newQuery()->where($this->primaryKey, $id)->delete();

            $this->attributes[$this->primaryKey] = null;

            $this->dispatcher->dispatch(new Deleted($this));
        }
    }
}
