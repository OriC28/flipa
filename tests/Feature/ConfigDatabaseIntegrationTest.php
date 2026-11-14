<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Config\Config;
use Core\Database\Database;
use Core\Database\Exceptions\ConnectionException;
use Core\Database\Exceptions\DatabaseConfigException;
use Tests\TestBase;

class ConfigDatabaseIntegrationTest extends TestBase
{
    protected string $tempConfigDir;
    protected Config $config;
    protected Database $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempConfigDir = sys_get_temp_dir() . '/flipa_config_' . uniqid();
        mkdir($this->tempConfigDir, 0777, true);

        $dbConfig = "<?php return [
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                ],
            ],
        ];";
        file_put_contents($this->tempConfigDir . '/database.php', $dbConfig);

        $this->config = new Config($this->tempConfigDir);
        $this->database = new Database($this->config);
    }
    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempConfigDir);
        parent::tearDown();
    }
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    public function test_config_provides_database_settings_and_database_establishes_connection(): void
    {
        $connection = $this->database->connection('sqlite');
        $pdo = $connection->getPdo();

        $this->assertInstanceOf(\PDOStatement::class, $pdo->query('SELECT 1;'));
    }
    public function test_database_reuses_same_connection_instance_through_config_driven_resolution(): void
    {
        $connection1 = $this->database->connection('sqlite');
        $connection2 = $this->database->connection('sqlite');

        $this->assertSame($connection1, $connection2);
    }
    public function test_connection_throws_exception_when_config_specifies_unsupported_default_driver(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage("The driver postgres is not supported. Try configuring SQLite or MySQL in config/database.php");

        $dbConfig = "<?php return [
            'default' => 'postgres',
            'connections' => [
                'postgres' => [
                    'driver' => 'postgres',
                    'database' => ':memory:',
                ],
            ],
        ];";
        file_put_contents($this->tempConfigDir . '/database.php', $dbConfig);

        // Recreate the config object to use the new configuration
        $config = new Config($this->tempConfigDir);
        $database = new Database($config);

        $connection = $database->connection('postgres');
        $connection->getPdo();
    }

    public function test_database_throws_exception_when_config_does_not_have_connections_driver(): void
    {
        $this->expectException(DatabaseConfigException::class);
        $this->expectExceptionMessage('Cannot connect to the database. Driver postgres is not supported.');

        $dbConfig = "<?php return [
            'default' => 'postgres',
            'connections' => [],
        ];";
        file_put_contents($this->tempConfigDir . '/database.php', $dbConfig);

        // Recreate the config object to use the new configuration
        $config = new Config($this->tempConfigDir);
        $database = new Database($config);

        $database->connection();
    }
}
