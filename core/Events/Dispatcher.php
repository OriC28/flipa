<?php

namespace Core\Events;

use Core\Events\Contracts\DispatcherInterface;
use Core\Events\Contracts\ListenerInterface;

class Dispatcher implements DispatcherInterface
{
    protected array $listeners = [];

    public function dispatch(object $event): bool
    {
        $eventClass = $event::class;

        if (isset($this->listeners[$eventClass])) {
            foreach ($this->listeners[$eventClass] as $listener) {
                $listen = app($listener);
                if ($listen->handle($event) === false) {
                    return false;
                }
            }
            return true;
        }
        return true;
    }

    public function listen(string $event, string|array $listeners): void
    {
        $listeners = \is_array($listeners) ? $listeners : [$listeners];

        foreach ($listeners as $listener) {
            if (!is_subclass_of($listener, ListenerInterface::class)) {
                throw new \Exception("Listener {$listener} must implement ListenerInterface.");
            }
        }

        $this->listeners[$event] = [...($this->listeners[$event] ?? []), ...$listeners];
    }
}
