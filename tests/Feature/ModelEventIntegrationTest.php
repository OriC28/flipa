<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Core\Database\Database;
use Core\Events\Dispatcher;
use Core\Events\ModelEvents\Created;
use Core\Events\ModelEvents\Creating;
use Core\Events\ModelEvents\Deleted;
use Core\Events\ModelEvents\Deleting;
use Core\Events\ModelEvents\Updated;
use Core\Events\ModelEvents\Updating;
use Tests\Fixtures\Events\CancelCreatingListener;
use Tests\Fixtures\Events\CancelDeletingListener;
use Tests\Fixtures\Events\CaptureModelListener;
use Tests\Fixtures\Events\CreatedListener;
use Tests\Fixtures\Events\CreatingListener;
use Tests\Fixtures\Events\DeletedListener;
use Tests\Fixtures\Events\DeletingListener;
use Tests\Fixtures\Events\EventLog;
use Tests\Fixtures\Events\UpdatedListener;
use Tests\Fixtures\Events\UpdatingListener;
use Tests\TestBase;

class ModelEventIntegrationTest extends TestBase
{
    private Dispatcher $dispatcher;
    private EventLog $log;
    private \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->log = new EventLog();
        $this->app->singleton(EventLog::class, fn() => $this->log);

        $this->dispatcher = new Dispatcher($this->app);
        $this->app->singleton(Dispatcher::class, fn() => $this->dispatcher);

        $this->pdo = app(Database::class)->connection('sqlite')->getPdo();
        CaptureModelListener::$capturedModel = null;
    }

    protected function tearDown(): void
    {
        $this->pdo->exec("DELETE FROM users");
        $this->pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'users'");
        CaptureModelListener::$capturedModel = null;
        parent::tearDown();
    }

    public function test_model_create_dispatches_creating_and_created_events(): void
    {
        $this->dispatcher->listen(Creating::class, CreatingListener::class);
        $this->dispatcher->listen(Created::class, CreatedListener::class);

        User::create(['name' => 'Event User', 'email' => 'events@flipa.local']);

        $this->assertSame(['Creating', 'Created'], $this->log->events);
    }

    public function test_model_save_update_dispatches_updating_and_updated_events(): void
    {
        $this->dispatcher->listen(Updating::class, UpdatingListener::class);
        $this->dispatcher->listen(Updated::class, UpdatedListener::class);

        $user = User::create(['name' => 'Before Update', 'email' => 'update@flipa.local']);
        $user->name = 'After Update';
        $user->save();

        $this->assertSame(['Updating', 'Updated'], $this->log->events);
    }

    public function test_model_delete_dispatches_deleting_and_deleted_events(): void
    {
        $this->dispatcher->listen(Deleting::class, DeletingListener::class);
        $this->dispatcher->listen(Deleted::class, DeletedListener::class);

        $user = User::create(['name' => 'Delete Me', 'email' => 'delete@flipa.local']);
        $user->delete();

        $this->assertSame(['Deleting', 'Deleted'], $this->log->events);
    }

    public function test_listener_returning_false_on_creating_prevents_database_insertion(): void
    {
        $this->dispatcher->listen(Creating::class, CancelCreatingListener::class);

        $user = User::create(['name' => 'Should Not Exist', 'email' => 'blocked@flipa.local']);

        $this->assertNull($user->id);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function test_listener_returning_false_on_deleting_prevents_database_deletion(): void
    {
        $this->dispatcher->listen(Deleting::class, CancelDeletingListener::class);

        $user = User::create(['name' => 'Protected User', 'email' => 'keep@flipa.local']);
        $userId = $user->id;

        $user->delete();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function test_event_carries_correct_model_instance(): void
    {
        $this->dispatcher->listen(Created::class, CaptureModelListener::class);

        $created = User::create(['name' => 'Payload User', 'email' => 'payload@flipa.local']);

        $this->assertSame($created, CaptureModelListener::$capturedModel);
        $this->assertSame('Payload User', CaptureModelListener::$capturedModel->name);
    }
}
