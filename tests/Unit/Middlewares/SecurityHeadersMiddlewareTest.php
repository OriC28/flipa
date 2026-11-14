<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    protected SecurityHeadersMiddleware $middleware;
    protected Request $request;
    protected array $headers;
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeadersMiddleware();
        $this->request = new Request(
            method: 'POST',
            uri: 'http://flipa.local',
            ip: '192.168.1.100',
            host: 'flipa.local',
            post: ['name' => 'flipa'],
            query: [],
            cookies: [],
            headers: [],
            files: []
        );
        $this->headers = [
            'Content-Security-Policy' => "default-src 'self'",
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];
    }
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->headers = [];
    }
    public function test_security_headers_middleware_adds_required_security_headers_to_response(): void
    {
        $next = fn() => new Response();

        $response = $this->middleware->handle($this->request, $next);

        foreach ($this->headers as $key => $value) {
            $this->assertArrayHasKey($key, $response->getHeaders());
            $this->assertSame($value, $response->getHeaders()[$key]);
        }
    }
    public function test_security_headers_middleware_returns_new_response_instance(): void
    {
        $originalResponse = new Response();
        $next = fn() => $originalResponse;

        $response = $this->middleware->handle($this->request, $next);

        $this->assertNotSame($originalResponse, $response);
        $this->assertEmpty($originalResponse->getHeaders());
        $this->assertSame($this->headers, $response->getHeaders());
    }
}
