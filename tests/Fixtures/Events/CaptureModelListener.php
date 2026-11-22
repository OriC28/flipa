<?php

declare(strict_types=1);

namespace Tests\Fixtures\Events;

use Core\Events\Contracts\ListenerInterface;
use Core\Events\ModelEvent;

class CaptureModelListener implements ListenerInterface
{
    public static ?object $capturedModel = null;

    public function handle(object $event): ?bool
    {
        if ($event instanceof ModelEvent) {
            self::$capturedModel = $event->getModel();
        }
        return true;
    }
}
