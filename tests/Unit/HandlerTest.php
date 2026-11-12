<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Exceptions\Handler;
use Core\Http\Exceptions\HttpException;
use Core\Http\Exceptions\MethodNotAllowedException;
use Core\Http\Exceptions\NotFoundException;
use Core\Http\Exceptions\ValidationException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Log\Logger;
use Core\View\View;
use Tests\TestBase;

class HandlerTest extends TestBase
{
    protected Handler $handler;
    protected string $originalEnv;
    private string $tempLogDir;
    private string $tempLogFile;
    private Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new Handler();
        $this->originalEnv = $_ENV['APP_ENV'] ?? 'development';
        $_ENV['APP_ENV'] = 'development';

        $this->tempLogDir = sys_get_temp_dir() . '/flipa_handler_logs_' . uniqid();
        $this->tempLogFile = 'handler.log';
        $this->logger = new Logger($this->tempLogDir, $this->tempLogFile);
        $this->app->singleton(Logger::class, fn() => $this->logger);
    }
    protected function tearDown(): void
    {
        $_ENV['APP_ENV'] = $this->originalEnv;

        $filePath = rtrim($this->tempLogDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->tempLogFile;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        if (is_dir($this->tempLogDir)) {
            rmdir($this->tempLogDir);
        }

        parent::tearDown();
    }
    private function createRequest(
        string $uri = '/',
        string $method = 'GET',
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
        return (fn() => (string) $response->body)->call($response);
    }
    public function test_calculates_status_code_from_http_exception(): void
    {
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);

        $response403 = $this->handler->render($request, new HttpException('Forbidden', 403));
        $this->assertSame(403, $response403->getStatusCode());

        $response404 = $this->handler->render($request, new NotFoundException());
        $this->assertSame(404, $response404->getStatusCode());
    }
    public function test_calculates_status_code_from_standard_exception_between_400_and_599(): void
    {
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);

        $response400 = $this->handler->render($request, new \Exception('Bad Request', 400));
        $this->assertSame(400, $response400->getStatusCode());

        $response503 = $this->handler->render($request, new \Exception('Service Unavailable', 503));
        $this->assertSame(503, $response503->getStatusCode());
    }
    public function test_defaults_status_code_to_500_for_generic_exception_with_non_http_code(): void
    {
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);

        $response0 = $this->handler->render($request, new \Exception('Zero code', 0));
        $this->assertSame(500, $response0->getStatusCode());

        $response200 = $this->handler->render($request, new \Exception('200 code', 200));
        $this->assertSame(500, $response200->getStatusCode());

        $response700 = $this->handler->render($request, new \Exception('700 code', 700));
        $this->assertSame(500, $response700->getStatusCode());
    }
    public function test_exposes_detailed_exception_message_in_development_environment(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);
        $exception = new \Exception('Sensitive database syntax error near column user_id');

        $response = $this->handler->render($request, $exception);
        $payload = json_decode($this->getResponseBody($response), true);

        $this->assertSame('Sensitive database syntax error near column user_id', $payload['error']);
    }
    public function test_masks_internal_error_message_with_generic_message_in_production_environment(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);
        $exception = new \Exception('Sensitive database syntax error near column user_id');

        $response = $this->handler->render($request, $exception);
        $payload = json_decode($this->getResponseBody($response), true);

        $this->assertSame('Internal Server Error.', $payload['error']);
        $this->assertStringNotContainsString('Sensitive database syntax error', $this->getResponseBody($response));
    }
    public function test_masks_not_found_message_in_production_for_non_http_exception(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);
        $exception = new \Exception('Secret file /etc/passwd not found', 404);

        $response = $this->handler->render($request, $exception);
        $payload = json_decode($this->getResponseBody($response), true);

        $this->assertSame('Resource Not Found.', $payload['error']);
    }
    public function test_masks_method_not_allowed_message_in_production_for_non_http_exception(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);
        $exception = new \Exception('Internal route matcher failed on DELETE', 405);

        $response = $this->handler->render($request, $exception);
        $payload = json_decode($this->getResponseBody($response), true);

        $this->assertSame('Method Not Allowed.', $payload['error']);
    }
    public function test_preserves_http_exception_message_in_production_environment(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);
        $exception = new HttpException('Custom client visible message', 400);

        $response = $this->handler->render($request, $exception);
        $payload = json_decode($this->getResponseBody($response), true);

        $this->assertSame('Custom client visible message', $payload['error']);
    }
    public function test_returns_json_response_when_accept_header_contains_application_json(): void
    {
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);
        $exception = new \Exception('Invalid request parameter', 400);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);
        $this->assertSame(
            ['error' => 'Invalid request parameter', 'code' => 400],
            json_decode($this->getResponseBody($response), true)
        );
    }
    public function test_returns_json_response_when_request_content_type_is_application_json(): void
    {
        $request = $this->createRequest(headers: ['Content-Type' => 'application/json']);
        $exception = new \Exception('Something broke', 500);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);
        $this->assertSame(
            ['error' => 'Something broke', 'code' => 500],
            json_decode($this->getResponseBody($response), true)
        );
    }
    public function test_renders_html_view_for_standard_error_status_codes_when_not_json(): void
    {
        $statusCodes = [404, 405, 419, 500];

        foreach ($statusCodes as $statusCode) {
            $request = $this->createRequest(headers: ['Accept' => 'text/html']);
            $exception = new HttpException("Error $statusCode occurred", $statusCode);

            $response = $this->handler->render($request, $exception);

            $this->assertSame($statusCode, $response->getStatusCode());
            $this->assertArrayNotHasKey('Content-Type', $response->getHeaders());
            $this->assertStringContainsString("<h1>$statusCode</h1>", $this->getResponseBody($response));
        }
    }
    public function test_validation_exception_formats_errors_array_in_json_response(): void
    {
        $errors = [
            'email' => ['The email field is required.'],
            'password' => ['The password must be at least 8 characters.'],
        ];
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);
        $exception = new ValidationException($errors);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);
        $this->assertSame(
            ['errors' => $errors],
            json_decode($this->getResponseBody($response), true)
        );
    }
    public function test_validation_exception_renders_errors_in_html_view(): void
    {
        $errors = [
            'email' => ['The email field is required.'],
        ];
        $request = $this->createRequest(headers: ['Accept' => 'text/html']);
        $exception = new ValidationException($errors);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(422, $response->getStatusCode());
        $body = $this->getResponseBody($response);
        $this->assertStringContainsString('<h1>422</h1>', $body);
        $this->assertStringContainsString('The email field is required.', $body);
    }
    public function test_method_not_allowed_exception_includes_allow_header_with_supported_methods(): void
    {
        $allowedMethods = ['GET', 'POST', 'HEAD'];
        $request = $this->createRequest(headers: ['Accept' => 'application/json']);
        $exception = new MethodNotAllowedException('Method Not Allowed', $allowedMethods);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertArrayHasKey('Allow', $response->getHeaders());
        $this->assertSame('GET, POST, HEAD', $response->getHeaders()['Allow']);
    }
    public function test_reports_exception_to_logger_with_error_level_and_context_details(): void
    {
        $mockLogger = $this->createMock(Logger::class);
        $exception = new \Exception('Logged failure message', 500);

        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                $this->callback(fn(string $msg) => str_contains($msg, 'Logged failure message')),
                $this->callback(function (array $context) use ($exception) {
                    return $context['exception'] === $exception::class
                        && $context['code'] === 500
                        && is_string($context['trace']);
                })
            );

        $this->app->singleton(Logger::class, fn() => $mockLogger);

        $request = $this->createRequest();
        $this->handler->render($request, $exception);
    }
    public function test_emergency_response_renders_500_view_when_error_view_is_missing(): void
    {
        $request = $this->createRequest(headers: ['Accept' => 'text/html']);
        $exception = new \Exception('Unsupported error code', 418);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('<h1>500</h1>', $this->getResponseBody($response));
    }

    public function test_emergency_response_masks_message_in_production_when_rendering_emergency_view(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $request = $this->createRequest(headers: ['Accept' => 'text/html']);
        $exception = new \Exception('Unsupported error code', 418);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(500, $response->getStatusCode());
        $body = $this->getResponseBody($response);
        $this->assertStringContainsString('<h1>500</h1>', $body);
        $this->assertStringContainsString('Internal Server Error', $body);
        $this->assertStringNotContainsString('No view was found', $body);
    }
    public function test_emergency_response_falls_back_to_plain_text_500_when_view_engine_fails(): void
    {
        $brokenView = new class(['']) extends View {
            public function render(string $view, array $data = []): string
            {
                throw new \RuntimeException('View engine is completely unavailable');
            }
        };

        $this->app->singleton(View::class, fn() => $brokenView);

        $request = $this->createRequest(headers: ['Accept' => 'text/html']);
        $exception = new \Exception('Unrecoverable error', 500);

        $response = $this->handler->render($request, $exception);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('500 Internal Server Error', $this->getResponseBody($response));
    }
}
