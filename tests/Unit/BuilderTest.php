<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Database\Database;
use Core\Database\Exceptions\DatabaseException;
use Core\Database\Query\Builder;
use Tests\TestBase;

class BuilderTest extends TestBase
{
    protected Builder $builder;
    protected \PDO $pdo;
    protected function setUp(): void
    {
        parent::setUp();
        $db = app(Database::class);

        $connection = $db->connection('sqlite');
        $this->pdo = $connection->getPdo();

        $this->builder = new Builder($connection);

        $this->pdo->exec("DELETE FROM users");
        $this->pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'users'");

        $stmt = $this->pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->execute(['Alice', 'alice@flipa.local']);
        $stmt->execute(['Bob', 'bob@flipa.local']);
        $stmt->execute(['Charlie', 'charlie@flipa.local']);
    }
    protected function tearDown(): void
    {
        $this->pdo->exec("DELETE FROM users");
        $this->pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'users'");
        parent::tearDown();
    }
    public function test_table_throws_exception_for_invalid_identifier(): void
    {
        $this->expectException(DatabaseException::class);
        $this->builder->table('users; DROP TABLE users');
    }
    public function test_where_throws_exception_for_unsupported_operator(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Operator (===) is not supported.');
        $this->builder->where('name', '===', 'flipa');
    }
    public function test_builder_constructs_select_query_with_conditions_and_bindings(): void
    {
        $this->assertSame(
            [
                ['id' => 1, 'name' => 'Alice', 'email' => 'alice@flipa.local'],
                ['id' => 2, 'name' => 'Bob', 'email' => 'bob@flipa.local'],
                ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@flipa.local']
            ],
            $this->builder->table('users')->get()
        );
        $this->assertSame(
            [
                ['id' => 1, 'name' => 'Alice', 'email' => 'alice@flipa.local'],
                ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@flipa.local']
            ],
            $this->builder->table('users')->where('name', 'LIKE', '%li%')->get()
        );
        $this->assertSame(
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@flipa.local'],
            $this->builder->table('users')->first()
        );
    }
    public function test_builder_constructs_order_by_clause_correctly(): void
    {
        $this->assertSame(
            [
                ['id' => 1, 'name' => 'Alice', 'email' => 'alice@flipa.local'],
                ['id' => 2, 'name' => 'Bob', 'email' => 'bob@flipa.local'],
                ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@flipa.local']
            ],
            $this->builder->table('users')->orderBy('name', 'asc')->get()
        );
        $this->assertSame(
            [
                ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@flipa.local'],
                ['id' => 2, 'name' => 'Bob', 'email' => 'bob@flipa.local'],
                ['id' => 1, 'name' => 'Alice', 'email' => 'alice@flipa.local']
            ],
            $this->builder->table('users')->orderBy('name', 'desc')->get()
        );
    }
    public function test_builder_throws_exception_for_unsupported_direction(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("The provided direction is not valid. Try 'asc' or 'desc'.");
        $this->builder->table('users')->orderBy('name', 'min')->get();
    }
    public function test_update_throws_exception_without_conditions(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Cannot update without conditions.');
        $this->builder->table('users')->update(['name' => 'Marie']);
    }
    public function test_delete_throws_exception_without_conditions(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Cannot delete without conditions.');
        $this->builder->table('users')->delete();
    }
    public function test_insert_creates_record_and_returns_affected_rows(): void
    {
        $this->assertSame(1, $this->builder->table('users')->insert(['name' => 'Marie', 'email' => 'marie@flipa.local']));
    }
    public function test_insert_get_id_returns_newly_created_id(): void
    {
        $this->assertSame(4, $this->builder->table('users')->insertGetId(['name' => 'Marie', 'email' => 'marie@flipa.local']));
    }
    public function test_update_modifies_records_with_conditions(): void
    {
        $this->builder->table('users')->where('name', 'Alice')->update(['name' => 'Alice Updated']);
        $this->assertSame('Alice Updated', $this->builder->table('users')->first()['name']);
    }
    public function test_delete_removes_records_with_conditions(): void
    {
        $this->builder->table('users')->where('name', 'Alice')->delete();
        $this->assertNotContains(
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@flipa.local'],
            $this->builder->table('users')->get()
        );
    }
    public function test_find_retrieves_single_record_by_primary_key(): void
    {
        $this->assertSame(
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@flipa.local'],
            $this->builder->table('users')->find(3)
        );
    }
    public function test_count_returns_total_number_of_records(): void
    {
        $this->assertSame(3, $this->builder->table('users')->count());
    }
}
