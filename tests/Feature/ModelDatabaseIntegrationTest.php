<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Core\Database\Database;
use Tests\TestBase;

class ModelDatabaseIntegrationTest extends TestBase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = app(Database::class)->connection('sqlite')->getPdo();
    }

    protected function tearDown(): void
    {
        $this->pdo->exec("DELETE FROM users");
        $this->pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'users'");
        parent::tearDown();
    }

    public function test_model_create_inserts_record_and_assigns_primary_key(): void
    {
        $user = User::create(['name' => 'Ana', 'email' => 'ana@test.com']);

        $this->assertNotNull($user->id);
        $this->assertGreaterThan(0, $user->id);

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user->id]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertSame('Ana', $row['name']);
        $this->assertSame('ana@test.com', $row['email']);
    }

    public function test_model_find_retrieves_existing_record_as_hydrated_instance(): void
    {
        $created = User::create(['name' => 'Carlos', 'email' => 'carlos@test.com']);

        $found = User::find($created->id);

        $this->assertInstanceOf(User::class, $found);
        $this->assertSame($created->id, $found->id);
        $this->assertSame('Carlos', $found->name);
        $this->assertSame('carlos@test.com', $found->email);
    }

    public function test_model_find_returns_null_for_non_existent_id(): void
    {
        $this->assertNull(User::find(9999));
    }

    public function test_model_save_updates_existing_record_without_duplicating(): void
    {
        $user = User::create(['name' => 'Initial Name', 'email' => 'update@test.com']);
        $userId = $user->id;

        $user->name = 'Updated Name';
        $user->save();

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        $this->assertSame(1, (int) $stmt->fetchColumn());

        $refetched = User::find($userId);
        $this->assertSame('Updated Name', $refetched->name);
    }

    public function test_model_delete_removes_record_and_nullifies_primary_key(): void
    {
        $user = User::create(['name' => 'To Delete', 'email' => 'delete@test.com']);
        $userId = $user->id;

        $user->delete();

        $this->assertNull($user->id);
        $this->assertNull(User::find($userId));

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function test_model_all_returns_all_records_as_independent_instances(): void
    {
        User::create(['name' => 'User One', 'email' => 'one@test.com']);
        User::create(['name' => 'User Two', 'email' => 'two@test.com']);
        User::create(['name' => 'User Three', 'email' => 'three@test.com']);

        $allUsers = User::all();

        $this->assertCount(3, $allUsers);
        $this->assertContainsOnlyInstancesOf(User::class, $allUsers);

        $allUsers[0]->name = 'Mutated In Memory';
        $freshFetch = User::all();
        $this->assertNotSame($allUsers[0]->name, $freshFetch[0]->name);
    }

    public function test_model_fillable_prevents_mass_assignment_of_unguarded_attributes(): void
    {
        $user = User::create([
            'name' => 'Protected User',
            'email' => 'safe@test.com',
            'is_admin' => 1,
        ]);

        $this->assertNull($user->is_admin);

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user->id]);
        $row = $stmt->fetch();

        $this->assertArrayNotHasKey('is_admin', $row);
    }
}
