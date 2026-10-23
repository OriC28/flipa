<?php

namespace Core\Events\Contracts;

interface ListenerInterface
{
    public function handle(object $event): ?bool;
}
