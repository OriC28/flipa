<?php

declare(strict_types=1);

namespace Core\Events;

use Core\Database\Model;

class ModelEvent extends Event
{
    public function __construct(protected Model $model) {}

    public function getModel(): Model
    {
        return $this->model;
    }
}
