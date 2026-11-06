<?php

declare(strict_types=1);

namespace Tests\Unit\Log;

use Core\Log\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    private string $tempLogDir;
    private string $tempLogFile;
    private Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempLogDir = sys_get_temp_dir() . '/flipa_test_logs_' . uniqid();
        $this->tempLogFile = 'test.log';

        $_ENV['APP_ENV'] = 'development';

        $this->logger = new Logger($this->tempLogDir, $this->tempLogFile);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $filePath = rtrim($this->tempLogDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->tempLogFile;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        if (is_dir($this->tempLogDir)) {
            rmdir($this->tempLogDir);
        }

        unset($_ENV['APP_ENV']);
    }
    public function test_logger_creates_directory_and_writes_to_file(): void
    {
        $this->logger->info('System started successfully');

        $filePath = rtrim($this->tempLogDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->tempLogFile;

        $this->assertDirectoryExists($this->tempLogDir);
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        // Verifies the format: [YYYY-MM-DD HH:MM:SS] development.INFO: System started successfully
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] development\.INFO: System started successfully/', $content);
    }
    public function test_logger_sanitizes_newlines_to_prevent_log_injection(): void
    {
        $maliciousMessage = "Login failure\n[2026-11-07 10:00:00] development.INFO: Admin login successful";

        $this->logger->error($maliciousMessage);

        $filePath = rtrim($this->tempLogDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->tempLogFile;
        $content = file_get_contents($filePath);

        $this->assertDoesNotMatchRegularExpression('/Login failure\r?\n\[/', $content);
        $this->assertStringContainsString('Login failure [2026-11-07 10:00:00] development.INFO: Admin login successful', trim($content));
    }
    public function test_logger_interpolates_context_and_formats_json_correctly(): void
    {
        $message = "The user {user_id} attempted to access {resource}";
        $context = ['user_id' => 42, 'resource' => '/admin'];

        $this->logger->warning($message, $context);

        $filePath = rtrim($this->tempLogDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->tempLogFile;
        $content = file_get_contents($filePath);

        $this->assertStringContainsString("The user 42", $content);
        $this->assertStringContainsString('{"user_id":42,"resource":"/admin"}', $content);
    }
}
