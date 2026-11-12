<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Events\Dispatcher;
use Tests\Fixtures\Events\EventLog;
use Tests\Fixtures\Events\InvalidListener;
use Tests\Fixtures\Events\SecondTestListener;
use Tests\Fixtures\Events\StopTestListener;
use Tests\Fixtures\Events\TestEvent;
use Tests\Fixtures\Events\TestListener;
use Tests\TestBase;

class DispatcherTest extends TestBase
{
    protected Dispatcher $dispatcher;
    protected EventLog $log;

    protected function setUp(): void
    {
        parent::setUp();

        $this->log = new EventLog();
        $this->app->singleton(EventLog::class, fn() => $this->log);
        $this->dispatcher = app(Dispatcher::class);
    }
    public function test_dispatch_calls_registered_listener(): void
    {
        $this->dispatcher->listen(TestEvent::class, TestListener::class);

        $this->dispatcher->dispatch(new TestEvent());

        $this->assertContains('TestListener', $this->log->events);
    }
    public function test_dispatch_calls_multiple_listeners_in_order(): void
    {
        $this->dispatcher->listen(TestEvent::class, TestListener::class);
        $this->dispatcher->listen(TestEvent::class, SecondTestListener::class);

        $this->dispatcher->dispatch(new TestEvent());

        $this->assertSame(['TestListener', 'SecondTestListener'], $this->log->events);
    }
    public function test_dispatch_stops_propagation_when_listener_returns_false(): void
    {
        // Register 3 listeners; the middle one returns false.
        $this->dispatcher->listen(TestEvent::class, TestListener::class);
        $this->dispatcher->listen(TestEvent::class, StopTestListener::class);
        $this->dispatcher->listen(TestEvent::class, SecondTestListener::class);

        $result = $this->dispatcher->dispatch(new TestEvent());

        // Verify that the third party did NOT execute
        $this->assertSame(['TestListener', 'StopTestListener'], $this->log->events);

        $this->assertFalse($result);
    }
    public function test_dispatch_resolves_listeners_from_container(): void
    {
        // When passing the class string, the Dispatcher should use $this->app->make()
        // If it were not resolved from the container, it would fail because TestListener requires EventLog in its constructor
        $this->dispatcher->listen(TestEvent::class, TestListener::class);

        $this->dispatcher->dispatch(new TestEvent());

        $this->assertContains('TestListener', $this->log->events);
    }
    public function test_listen_throws_exception_for_invalid_listener(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Listener ' . InvalidListener::class . ' must implement ListenerInterface.');

        $this->dispatcher->listen(TestEvent::class, InvalidListener::class);
    }
    public function test_dispatch_returns_true_for_unregistered_event(): void
    {
        $result = $this->dispatcher->dispatch(new TestEvent());

        $this->assertTrue($result);
        $this->assertEmpty($this->log->events);
    }
}
