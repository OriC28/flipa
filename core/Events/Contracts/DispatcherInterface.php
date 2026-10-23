<?php

namespace Core\Events\Contracts;

interface DispatcherInterface
{
    public function dispatch(object $event): bool;
}
