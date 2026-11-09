<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected Config $config;
    protected string $tempPathConfig;
    protected array $files = [];
    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPathConfig = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_config_' . uniqid();
        $this->files = [
            'database' => [
                'file' => $this->createConfigFilePath('database.php', $this->tempPathConfig),
                'content' => "
                    <?php 
                    return [
                            'default' => 'sqlite',
                            'connections' => [
                                'sqlite' => [
                                    'driver' => 'sqlite',
                                    'url' => '',
                                    'database' => 'database.sqlite',
                                ]
                            ]
                        ];
                    ?>
                    "
            ],
            'app' => [
                'file' => $this->createConfigFilePath('app.php', $this->tempPathConfig),
                'content' => "
                    <?php
                        return [
                            'name' => 'flipa'
                        ];
                    ?>
                "
            ],
        ];
        mkdir(directory: $this->tempPathConfig, recursive: true);

        $this->createAllFileWithConfig($this->files);

        $this->config = new Config($this->tempPathConfig);
    }

    private function createConfigFilePath(string $filename, string $path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    }

    private function createAllFileWithConfig(array $files)
    {
        foreach ($files as $_ => $config) {
            if ($handle = fopen($config['file'], 'w')) {
                fwrite($handle, $config['content']);
                fclose($handle);
            }
        }
    }
    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->files as $_ => $config) {
            if (file_exists($config['file'])) {
                unlink($config['file']);
            }
        }
        if (is_dir($this->tempPathConfig)) {
            rmdir($this->tempPathConfig);
        }
        $this->files = [];
    }
    public function test_config_resolves_nested_values_using_dot_notation(): void
    {
        $this->assertSame('sqlite', $this->config->get('database.connections.sqlite.driver'));
        $this->assertSame('flipa', $this->config->get('app.name'));
    }
    public function test_config_returns_default_value_when_key_does_not_exist(): void
    {
        $this->assertSame('fallback', $this->config->get('database.inexistent', 'fallback'));
    }
    public function test_config_returns_null_when_default_value_was_not_provided(): void
    {
        $this->assertNull($this->config->get('database.inexistent'));
    }

    public function test_config_returns_default_when_accessing_subkey_of_non_array_value(): void
    {
        $this->assertSame('fallback', $this->config->get('database.default.foo', 'fallback'));
    }

    public function test_config_returns_null_when_accessing_subkey_of_non_array_value_without_default(): void
    {
        $this->assertNull($this->config->get('database.default.foo'));
    }

    public function test_config_without_directory_does_not_throw_an_exception(): void
    {
        $emptyDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'empty_config_' . uniqid();
        mkdir($emptyDir);

        $configWithoutDirectory = new Config($emptyDir);
        $this->assertNull($configWithoutDirectory->get('any.foo.key'));

        rmdir($emptyDir);
    }
}
