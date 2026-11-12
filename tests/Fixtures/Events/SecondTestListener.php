<?php

declare(strict_types=1);

namespace Tests\Fixtures\Events;

use Core\Events\Contracts\ListenerInterface;

class SecondTestListener implements ListenerInterface
{
    public function __construct(private EventLog $log) {}

    public function handle(object $event): ?bool
    {
        $this->log->events[] = 'SecondTestListener';
        return true;
    }
}
