<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Http\Exceptions\MethodNotAllowedException;
use Core\Http\Exceptions\NotFoundException;
use Core\Http\Request;
use Core\Http\Router;
use Tests\Fixtures\Http\IntegrationTestController;
use Tests\TestBase;

class RouterDispatchIntegrationTest extends TestBase
{
    protected Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = $this->app->make(Router::class);
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

    public function test_router_dispatches_closure_route_with_injected_request_and_parameters(): void
    {
        $this->router->get('/api/users/{id}', function (Request $request, string $id) {
            return "User {$id} fetched via " . $request->getMethod();
        });

        $request = $this->createRequest(method: 'GET', uri: '/api/users/42');
        $this->app->singleton(Request::class, fn() => $request);

        $result = $this->router->dispatch($request);

        $this->assertSame('User 42 fetched via GET', $result);
    }

    public function test_router_dispatches_controller_method_resolving_dependencies_from_container(): void
    {
        $this->router->get('/items/{id}', [IntegrationTestController::class, 'show']);

        $request = $this->createRequest(method: 'GET', uri: '/items/99');
        $result = $this->router->dispatch($request);

        $this->assertSame('Item 99 with dependency foo', $result);
    }

    public function test_router_dispatches_controller_with_form_request_dependency_injection(): void
    {
        $this->router->post('/submit-form', [IntegrationTestController::class, 'submit']);

        $request = $this->createRequest(
            method: 'POST',
            uri: '/submit-form',
            post: ['name' => 'Flipa Framework', 'email' => 'dev@flipa.local']
        );
        $this->app->singleton(Request::class, fn() => $request);

        $result = $this->router->dispatch($request);

        $this->assertSame('Processed: Flipa Framework', $result);
    }

    public function test_router_returns_not_found_for_unregistered_uri_in_bootstrapped_app(): void
    {
        $request = $this->createRequest(method: 'GET', uri: '/route-that-does-not-exist');

        $this->expectException(NotFoundException::class);
        $this->router->dispatch($request);
    }

    public function test_router_returns_method_not_allowed_with_correct_headers_in_bootstrapped_app(): void
    {
        $this->router->get('/only-get-endpoint', fn() => 'ok');

        $request = $this->createRequest(method: 'POST', uri: '/only-get-endpoint');

        try {
            $this->router->dispatch($request);
            $this->fail('Expected MethodNotAllowedException was not thrown.');
        } catch (MethodNotAllowedException $e) {
            $this->assertContains('GET', $e->getAllowedMethods());
        }
    }
}
