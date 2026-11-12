<?php

declare(strict_types=1);

namespace Tests\Fixtures\Events;

use Core\Events\Contracts\ListenerInterface;

class CancelCreatingListener implements ListenerInterface
{
    public function handle(object $event): ?bool
    {
        return false;
    }
}
