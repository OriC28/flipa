<?php

declare(strict_types=1);

namespace Core\Events\Contracts;

interface DispatcherInterface
{
    public function dispatch(EventInterface $event): bool;
}
