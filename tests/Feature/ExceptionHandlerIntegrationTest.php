<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Exceptions\Handler;
use Core\Http\Exceptions\MethodNotAllowedException;
use Core\Http\Exceptions\NotFoundException;
use Core\Http\Exceptions\ValidationException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Log\Logger;
use Tests\TestBase;

class ExceptionHandlerIntegrationTest extends TestBase
{
    private Handler $handler;
    private string $tempLogDir;
    private string $tempLogFile;
    private string $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new Handler();
        $this->originalEnv = $_ENV['APP_ENV'] ?? 'development';
        $_ENV['APP_ENV'] = 'development';

        $this->tempLogDir = sys_get_temp_dir() . '/flipa_integration_logs_' . uniqid();
        $this->tempLogFile = 'flipa.log';
        $logger = new Logger($this->tempLogDir, $this->tempLogFile);
        $this->app->singleton(Logger::class, fn() => $logger);
    }

    protected function tearDown(): void
    {
        $_ENV['APP_ENV'] = $this->originalEnv;

        $logPath = $this->tempLogDir . DIRECTORY_SEPARATOR . $this->tempLogFile;
        if (file_exists($logPath)) {
            unlink($logPath);
        }
        if (is_dir($this->tempLogDir)) {
            rmdir($this->tempLogDir);
        }

        parent::tearDown();
    }

    private function createRequest(
        string $method = 'GET',
        string $uri = '/',
        array $headers = []
    ): Request {
        $server = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'    => $uri,
            'REMOTE_ADDR'    => '127.0.0.1',
            'HTTP_HOST'      => 'localhost',
        ];

        foreach ($headers as $key => $value) {
            $normalizedKey = str_replace('-', '_', strtoupper($key));
            if ($normalizedKey === 'CONTENT_TYPE') {
                $server['CONTENT_TYPE'] = $value;
            } else {
                $server["HTTP_$normalizedKey"] = $value;
            }
        }

        return new Request(
            method: $method,
            uri: $uri,
            ip: '127.0.0.1',
            host: 'localhost',
            post: [],
            query: [],
            cookies: [],
            headers: $server,
            files: []
        );
    }

    private function getResponseBody(Response $response): string
    {
        return (fn() => (string) $this->body)->call($response);
    }

    private function getLogContent(): string
    {
        $logPath = $this->tempLogDir . DIRECTORY_SEPARATOR . $this->tempLogFile;
        return file_exists($logPath) ? file_get_contents($logPath) : '';
    }

    public function test_handler_renders_404_view_and_logs_not_found_exception(): void
    {
        $request = $this->createRequest(uri: '/non-existent');
        $exception = new NotFoundException('Resource requested was not found');

        $response = $this->handler->render($request, $exception);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('<h1>404</h1>', $this->getResponseBody($response));

        $logContent = $this->getLogContent();
        $this->assertStringContainsString('Resource requested was not found', $logContent);
        $this->assertStringContainsString('NotFoundException', $logContent);
    }

    public function test_handler_renders_405_view_with_allow_header_and_logs_exception(): void
    {
        $request = $this->createRequest(method: 'POST', uri: '/items');
        $exception = new MethodNotAllowedException('Action not allowed', ['GET', 'POST']);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, POST', $response->getHeaders()['Allow']);
        $this->assertStringContainsString('<h1>405</h1>', $this->getResponseBody($response));

        $logContent = $this->getLogContent();
        $this->assertStringContainsString('Action not allowed', $logContent);
        $this->assertStringContainsString('MethodNotAllowedException', $logContent);
    }

    public function test_handler_renders_422_view_with_validation_errors(): void
    {
        $request = $this->createRequest(method: 'POST', uri: '/register');
        $exception = new ValidationException(['email' => ['The email field is required.']]);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(422, $response->getStatusCode());
        $body = $this->getResponseBody($response);
        $this->assertStringContainsString('<h1>422</h1>', $body);
        $this->assertStringContainsString('The email field is required.', $body);
    }

    public function test_handler_returns_json_error_with_log_entry_for_json_request(): void
    {
        $request = $this->createRequest(
            method: 'POST',
            uri: '/api/resource',
            headers: ['Accept' => 'application/json']
        );
        $exception = new \Exception('Database query failed', 500);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);

        $payload = json_decode($this->getResponseBody($response), true);
        $this->assertSame('Database query failed', $payload['error']);
        $this->assertSame(500, $payload['code']);

        $this->assertStringContainsString('Database query failed', $this->getLogContent());
    }

    public function test_handler_masks_message_in_production_and_logs_real_error(): void
    {
        $_ENV['APP_ENV'] = 'production';

        $request = $this->createRequest(headers: ['Accept' => 'application/json']);
        $exception = new \Exception('CRITICAL: database password secret_123 leaked');

        $response = $this->handler->render($request, $exception);

        $this->assertSame(500, $response->getStatusCode());

        $payload = json_decode($this->getResponseBody($response), true);
        $this->assertSame('Internal Server Error.', $payload['error']);
        $this->assertStringNotContainsString('secret_123', $this->getResponseBody($response));

        $this->assertStringContainsString('CRITICAL: database password secret_123 leaked', $this->getLogContent());
    }
}
