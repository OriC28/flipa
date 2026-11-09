<?php

declare(strict_types=1);

namespace Tests;

use Core\Container\Container;
use Core\Database\Database;
use Core\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Override;

class TestBase extends TestCase
{
    protected ?Application $app;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = require __DIR__ . '/../bootstrap/app.php';
        restore_exception_handler();

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

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        Container::clearInstance();
        $this->app = null;
    }
}
