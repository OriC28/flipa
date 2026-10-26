<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Database\Query\Builder;
use Core\Events\Dispatcher;
use JsonSerializable;
use Core\Events\ModelEvents\Created;
use Core\Events\ModelEvents\Creating;
use Core\Events\ModelEvents\Updating;
use Core\Events\ModelEvents\Updated;
use Core\Events\ModelEvents\Deleted;
use Core\Events\ModelEvents\Deleting;

class Model implements JsonSerializable
{
    protected string $table = "";
    protected array $attributes = [];
    protected ?Connection $connection = null;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    private ?Dispatcher $dispatcher = null;

    public function __construct()
    {
        $this->table = $this->tableNameAutoDiscovery();
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
        return (new Builder($this->getConnection()))->table($this->table);
    }
    private function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            if (\in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    private function getConnection(): Connection
    {
        if ($this->connection === null) {
            $db = app(Database::class);
            $this->connection = $db->connection();
        }
        return $this->connection;
    }

    private function getDispatcher(): Dispatcher
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = app(Dispatcher::class);
        }
        return $this->dispatcher;
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
        $row = $model->newQuery()->where($model->primaryKey, $id)->first();
        if ($row === null) return null;
        return $model->hydrate($row);
    }
    public function save(): void
    {
        if (isset($this->attributes[$this->primaryKey])) {
            if (!$this->getDispatcher()->dispatch(new Updating($this))) return;

            $id = $this->attributes[$this->primaryKey];
            $attributesWithoutPrimaryKey = array_diff_key($this->attributes, [$this->primaryKey => true]);
            $this->newQuery()->where($this->primaryKey, $id)->update($attributesWithoutPrimaryKey);

            $this->getDispatcher()->dispatch(new Updated($this));
            return;
        }
        if (!$this->getDispatcher()->dispatch(new Creating($this))) return;

        $newId = $this->newQuery()->insertGetId($this->attributes);
        $this->attributes[$this->primaryKey] = $newId;

        $this->getDispatcher()->dispatch(new Created($this));
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
            if (!$this->getDispatcher()->dispatch(new Deleting($this))) return;

            $id = $this->attributes[$this->primaryKey];
            $this->newQuery()->where($this->primaryKey, $id)->delete();

            $this->attributes[$this->primaryKey] = null;

            $this->getDispatcher()->dispatch(new Deleted($this));
        }
    }
}
