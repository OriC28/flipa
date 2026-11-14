<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use Core\Http\Exceptions\HttpException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\CsrfMiddleware;
use Tests\TestBase;

class CsrfMiddlewareTest extends TestBase
{
    protected CsrfMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CsrfMiddleware();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        parent::tearDown();
    }

    public function test_csrf_middleware_bypasses_validation_for_safe_methods(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/api/data',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: [],
            query: [],
            cookies: [],
            headers: [],
            files: []
        );
        $next = fn() => new Response(body: 'ok');

        $response = $this->middleware->handle($request, $next);

        $body = (fn() => $this->body)->call($response);
        $this->assertSame('ok', $body);
    }

    public function test_csrf_middleware_throws_405_for_unsupported_methods(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Method Not Allowed');

        $request = new Request(
            method: 'TRACE',
            uri: '/api/data',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: [],
            query: [],
            cookies: [],
            headers: [],
            files: []
        );
        $next = fn() => new Response();

        $this->middleware->handle($request, $next);
    }

    public function test_csrf_middleware_throws_419_when_token_is_missing(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('The CSRF token is missing.');

        $_SESSION['_csrf_token'] = 'valid_session_token';

        $request = new Request(
            method: 'POST',
            uri: '/api/submit',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: [],
            query: [],
            cookies: [],
            headers: [],
            files: []
        );
        $next = fn() => new Response();

        $this->middleware->handle($request, $next);
    }

    public function test_csrf_middleware_throws_419_when_tokens_do_not_match(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('The CSRF token provided does not match the session token.');

        $_SESSION['_csrf_token'] = 'valid_session_token';

        $request = new Request(
            method: 'POST',
            uri: '/api/submit',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: ['_csrf_token' => 'invalid_user_token'],
            query: [],
            cookies: [],
            headers: [],
            files: []
        );
        $next = fn() => new Response();

        $this->middleware->handle($request, $next);
    }

    public function test_csrf_middleware_allows_request_with_valid_post_token(): void
    {
        $_SESSION['_csrf_token'] = 'matching_token_123';

        $request = new Request(
            method: 'POST',
            uri: '/api/submit',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: ['_csrf_token' => 'matching_token_123'],
            query: [],
            cookies: [],
            headers: [],
            files: []
        );
        $next = fn() => new Response(body: 'success');

        $response = $this->middleware->handle($request, $next);

        $body = (fn() => $this->body)->call($response);
        $this->assertSame('success', $body);
    }

    public function test_csrf_middleware_allows_request_with_valid_header_token(): void
    {
        $_SESSION['_csrf_token'] = 'matching_header_token_456';

        $request = new Request(
            method: 'PUT',
            uri: '/api/update',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: [],
            query: [],
            cookies: [],
            headers: ['HTTP_X_CSRF_TOKEN' => 'matching_header_token_456'],
            files: []
        );
        $next = fn() => new Response(body: 'updated');

        $response = $this->middleware->handle($request, $next);

        $body = (fn() => $this->body)->call($response);
        $this->assertSame('updated', $body);
    }
}
