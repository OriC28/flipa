<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\StartSessionMiddleware;
use PHPUnit\Framework\TestCase;

class StartSessionMiddlewareTest extends TestCase
{
    protected StartSessionMiddleware $middleware;
    protected Request $request;
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new StartSessionMiddleware();
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
    }
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        parent::tearDown();
    }
    public function test_start_session_middleware_initializes_session_and_generates_csrf_token(): void
    {
        $next = fn() => new Response();

        $this->middleware->handle($this->request, $next);

        $this->assertNotEmpty($_SESSION['_csrf_token']);
        $this->assertIsString($_SESSION['_csrf_token']);
        $this->assertSame(64, \strlen($_SESSION['_csrf_token']));
        $this->assertTrue(ctype_xdigit($_SESSION['_csrf_token']));
    }
    public function test_start_session_middleware_does_not_regenerate_token_if_session_already_active(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['_csrf_token'] = 'my_existing_token_1234567890abcdef';
        $next = fn() => new Response();

        $this->middleware->handle($this->request, $next);

        $this->assertSame('my_existing_token_1234567890abcdef', $_SESSION['_csrf_token']);
    }
}
