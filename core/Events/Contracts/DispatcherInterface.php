<?php

namespace Core\Events\Contracts;

interface DispatcherInterface
{
    public function dispatch(EventInterface $event): bool;
}
