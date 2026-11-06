<?php

declare(strict_types=1);

namespace Tests;

use Core\Database\Database;
use PHPUnit\Framework\TestCase;
use Override;

class TestBase extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $db = app(Database::class);
        $pdo = $db->connection('sqlite')->getPdo();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL
            )
        ");
    }
}
