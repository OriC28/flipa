<?php

declare(strict_types=1);

namespace Tests\Fixtures\Events;

use Core\Events\Contracts\ListenerInterface;

class UpdatedListener implements ListenerInterface
{
    public function __construct(private EventLog $log) {}

    public function handle(object $event): ?bool
    {
        $this->log->events[] = 'Updated';
        return true;
    }
}
