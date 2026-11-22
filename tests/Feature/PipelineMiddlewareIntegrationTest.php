<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Http\Exceptions\HttpException;
use Core\Http\Exceptions\OriginNotAllowedException;
use Core\Http\Pipeline;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\CorsMiddleware;
use Core\Middleware\CsrfMiddleware;
use Core\Middleware\SecurityHeadersMiddleware;
use Tests\TestBase;

class PipelineMiddlewareIntegrationTest extends TestBase
{
    protected Pipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pipeline = new Pipeline($this->app);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['_csrf_token']);
        parent::tearDown();
    }

    private function createRequest(
        string $method = 'GET',
        string $uri = '/',
        array $post = [],
        array $query = [],
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
            post: $post,
            query: $query,
            cookies: [],
            headers: $server,
            files: []
        );
    }

    public function test_security_headers_middleware_injects_all_required_headers_into_response(): void
    {
        $request = $this->createRequest();

        $response = $this->pipeline
            ->send($request)
            ->through([SecurityHeadersMiddleware::class])
            ->then(fn() => new Response(body: 'ok'));

        $headers = $response->getHeaders();

        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $this->assertArrayHasKey('X-Content-Type-Options', $headers);
        $this->assertArrayHasKey('X-Frame-Options', $headers);
        $this->assertArrayHasKey('Referrer-Policy', $headers);
        $this->assertArrayHasKey('Permissions-Policy', $headers);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('DENY', $headers['X-Frame-Options']);
    }

    public function test_cors_middleware_allows_request_from_configured_origin(): void
    {
        $request = $this->createRequest(headers: ['Origin' => 'http://flipa.local']);

        $response = $this->pipeline
            ->send($request)
            ->through([CorsMiddleware::class])
            ->then(fn() => new Response(body: 'ok'));

        $headers = $response->getHeaders();

        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertSame('http://flipa.local', $headers['Access-Control-Allow-Origin']);
        $this->assertSame('true', $headers['Access-Control-Allow-Credentials']);
    }

    public function test_cors_middleware_rejects_request_from_unconfigured_origin(): void
    {
        $request = $this->createRequest(headers: ['Origin' => 'http://unauthorized-domain.com']);

        $this->expectException(OriginNotAllowedException::class);

        $this->pipeline
            ->send($request)
            ->through([CorsMiddleware::class])
            ->then(fn() => new Response(body: 'ok'));
    }

    public function test_cors_middleware_returns_204_for_preflight_options_request(): void
    {
        $destinationExecuted = false;
        $request = $this->createRequest(method: 'OPTIONS', headers: ['Origin' => 'http://flipa.local']);

        $response = $this->pipeline
            ->send($request)
            ->through([CorsMiddleware::class])
            ->then(function () use (&$destinationExecuted) {
                $destinationExecuted = true;
                return new Response(body: 'destination');
            });

        $this->assertFalse($destinationExecuted);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('http://flipa.local', $response->getHeaders()['Access-Control-Allow-Origin']);
    }

    public function test_csrf_middleware_blocks_post_request_without_valid_token(): void
    {
        $_SESSION['_csrf_token'] = 'expected_secret_token';
        $request = $this->createRequest(method: 'POST', post: []);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(419);

        $this->pipeline
            ->send($request)
            ->through([CsrfMiddleware::class])
            ->then(fn() => new Response(body: 'ok'));
    }

    public function test_csrf_middleware_allows_post_request_with_valid_session_token(): void
    {
        $_SESSION['_csrf_token'] = 'session_token_12345';
        $request = $this->createRequest(
            method: 'POST',
            post: ['_csrf_token' => 'session_token_12345']
        );

        $response = $this->pipeline
            ->send($request)
            ->through([CsrfMiddleware::class])
            ->then(fn() => new Response(body: 'destination reached'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_pipeline_executes_multiple_real_middlewares_in_correct_order(): void
    {
        $request = $this->createRequest(headers: ['Origin' => 'http://flipa.local']);

        $response = $this->pipeline
            ->send($request)
            ->through([SecurityHeadersMiddleware::class, CorsMiddleware::class])
            ->then(fn() => new Response(body: 'payload'));

        $headers = $response->getHeaders();

        $this->assertSame('http://flipa.local', $headers['Access-Control-Allow-Origin']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('DENY', $headers['X-Frame-Options']);
    }
}
