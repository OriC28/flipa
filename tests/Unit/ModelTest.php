<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Database\Database;
use Core\Events\Dispatcher;
use Core\Events\ModelEvents\Created;
use Core\Events\ModelEvents\Creating;
use Tests\Fixtures\Events\CancelCreatingListener;
use Tests\Fixtures\Events\CreatedListener;
use Tests\Fixtures\Events\CreatingListener;
use Tests\Fixtures\Events\EventLog;
use Tests\Fixtures\Models\BlogPost;
use Tests\Fixtures\Models\TestUser;
use Tests\Fixtures\Models\User;
use Tests\TestBase;

class ModelTest extends TestBase
{
    protected \PDO $pdo;
    protected function setUp(): void
    {
        parent::setUp();

        $db = app(Database::class);
        $this->pdo = $db->connection('sqlite')->getPdo();

        $this->pdo->exec("DELETE FROM users");
        $this->pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'users'");

        $stmt = $this->pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->execute(['Alice', 'alice@flipa.local']);
        $stmt->execute(['Gabriel', 'gabriel@flipa.local']);

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS blog_posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                secret_token TEXT
            )
        ");
    }

    protected function tearDown(): void
    {
        $db = app(Database::class);
        $this->pdo = $db->connection('sqlite')->getPdo();
        $this->pdo->exec("DROP TABLE IF EXISTS blog_posts");
        $this->pdo->exec("DELETE FROM users");
        $this->pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'users'");

        parent::tearDown();
    }
    public function test_model_auto_discovers_table_name_for_camel_case_class_names(): void
    {
        $user = new User();
        $blog = new BlogPost();
        $this->assertSame('users', (fn() => $user->table)->call($user));
        $this->assertSame('blog_posts', (fn() => $blog->table)->call($blog));
    }
    public function test_model_fills_only_fillable_attributes(): void
    {
        $user = new TestUser();

        $reflectionMethod = new \ReflectionMethod($user, 'fill');
        $reflectionMethod->invoke($user, ['name' => 'foo', 'password' => 'secret']);

        $attributes = (fn() => $user->attributes)->call($user);

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayNotHasKey('password', $attributes);
    }
    public function test_model_hides_attributes_in_json_and_debug_output(): void
    {
        $user = new User();
        $user->secret_token = bin2hex(random_bytes(32));

        $this->assertStringNotContainsString('secret_token', json_encode($user));
        $this->assertArrayNotHasKey('secret_token', $user->__debugInfo());
    }
    public function test_model_magic_get_and_set_allows_dynamic_attribute_access(): void
    {
        $user = new User();
        $user->name = 'foo';

        $attributes = (fn() => $user->attributes)->call($user);

        $this->assertArrayHasKey('name', $attributes);
        $this->assertSame('foo', $user->__get('name'));
    }
    public function test_model_all_returns_hydrated_model_instances(): void
    {
        foreach (User::all() as $user) {
            $this->assertInstanceOf(User::class, $user);
        }
    }
    public function test_model_find_returns_hydrated_instance_or_null(): void
    {
        $user = User::find(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@flipa.local'],
            (fn() => $user->attributes)->call($user)
        );
        $this->assertNull(User::find(999));
    }
    public function test_model_create_persists_and_returns_hydrated_instance(): void
    {
        $user = User::create(['name' => 'Eve', 'email' => 'eve@flipa.local']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(3, $user->id);
    }
    public function test_model_fires_creating_and_created_events_on_save(): void
    {
        $log = new EventLog();
        $this->app->singleton(EventLog::class, fn() => $log);

        app(Dispatcher::class)->listen(Creating::class, CreatingListener::class);
        app(Dispatcher::class)->listen(Created::class, CreatedListener::class);

        User::create(['name' => 'Eve', 'email' => 'eve@flipa.local']);

        $this->assertSame(['Creating', 'Created'], $log->events);
    }

    public function test_model_canceling_creating_event_prevents_database_insert(): void
    {
        app(Dispatcher::class)->listen(Creating::class, CancelCreatingListener::class);

        User::create(['name' => 'Eve', 'email' => 'eve@flipa.local']);

        $count = $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $this->assertSame(2, (int) $count);
    }
}
