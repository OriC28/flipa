<?php

namespace Core\Events;

use Core\Database\Model;

class Event
{
    public function __construct(protected Model $model) {}

    public function getModel(): Model
    {
        return $this->model;
    }
}
