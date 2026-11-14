<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use Core\Http\Exceptions\OriginNotAllowedException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\CorsMiddleware;
use Tests\TestBase;

class CorsMiddlewareTest extends TestBase
{
    protected CorsMiddleware $middleware;
    protected string $tempConfigPath;
    protected string $tempBaseDir;
    protected string $originalBasePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CorsMiddleware();

        $this->tempBaseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'flipa_test_' . uniqid();
        mkdir($this->tempBaseDir . DIRECTORY_SEPARATOR . 'config', 0777, true);

        $reflection = new \ReflectionProperty($this->app, 'basePath');
        $this->originalBasePath = $reflection->getValue($this->app);
        $reflection->setValue($this->app, $this->tempBaseDir);

        $this->tempConfigPath = config_path('cors.php');

        $configContent = "<?php return [
            'allowed_origins' => ['http://flipa.local', 'http://localhost:3000'],
        ];";

        file_put_contents($this->tempConfigPath, $configContent);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempConfigPath)) {
            unlink($this->tempConfigPath);
        }
        if (is_dir($this->tempBaseDir . DIRECTORY_SEPARATOR . 'config')) {
            rmdir($this->tempBaseDir . DIRECTORY_SEPARATOR . 'config');
        }
        if (is_dir($this->tempBaseDir)) {
            rmdir($this->tempBaseDir);
        }

        $reflection = new \ReflectionProperty($this->app, 'basePath');
        $reflection->setValue($this->app, $this->originalBasePath);

        parent::tearDown();
    }

    public function test_cors_middleware_passes_through_when_origin_header_is_missing(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/api/users',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: [],
            query: [],
            cookies: [],
            headers: [],
            files: []
        );
        $expectedResponse = new Response(body: 'ok');
        $next = fn() => $expectedResponse;

        $response = $this->middleware->handle($request, $next);

        $this->assertSame($expectedResponse, $response);
        $this->assertEmpty($response->getHeaders());
    }

    public function test_cors_middleware_throws_exception_for_disallowed_origin(): void
    {
        $this->expectException(OriginNotAllowedException::class);
        $this->expectExceptionMessage('The origin of the request is not in the list of allowed domains.');

        $request = new Request(
            method: 'GET',
            uri: '/api/users',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: [],
            query: [],
            cookies: [],
            headers: ['HTTP_ORIGIN' => 'http://malicious.com'],
            files: []
        );
        $next = fn() => new Response(body: 'ok');

        $this->middleware->handle($request, $next);
    }

    public function test_cors_middleware_handles_preflight_options_request(): void
    {
        $request = new Request(
            method: 'OPTIONS',
            uri: '/api/users',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: [],
            query: [],
            cookies: [],
            headers: ['HTTP_ORIGIN' => 'http://flipa.local'],
            files: []
        );
        $next = fn() => throw new \Exception('Next was called!');

        $response = $this->middleware->handle($request, $next);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('http://flipa.local', $response->getHeaders()['Access-Control-Allow-Origin']);
    }

    public function test_cors_middleware_appends_cors_headers_to_successful_response(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/api/users',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: [],
            query: [],
            cookies: [],
            headers: ['HTTP_ORIGIN' => 'http://flipa.local'],
            files: []
        );
        $next = fn() => new Response(body: 'Hello from Flipa');

        $response = $this->middleware->handle($request, $next);

        $CORSHeadersExpected = [
            'Access-Control-Allow-Origin' => 'http://flipa.local',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization, Accept',
            'Access-Control-Allow-Credentials' => 'true',
        ];

        $this->assertSame($CORSHeadersExpected, $response->getHeaders());

        $body = (fn() => $this->body)->call($response);
        $this->assertSame('Hello from Flipa', $body);
    }
}
