<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Exceptions\MethodNotAllowedException;
use Core\Http\Exceptions\NotFoundException;
use Core\Http\Request;
use Core\Http\Route;
use Core\Http\Router;
use Tests\Fixtures\Http\TestController;
use Tests\TestBase;

class RouterTest extends TestBase
{
    protected Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router($this->app);
    }
    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        parent::tearDown();
    }
    public function test_router_registers_routes_for_all_supported_http_methods(): void
    {
        $methods = [
            'GET' => 'get',
            'POST' => 'post',
            'PUT' => 'put',
            'PATCH' => 'patch',
            'DELETE' => 'delete',
        ];

        foreach ($methods as $key => $value) {
            $route = $this->router->{$value}('/test', fn() => 'ok');
            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame($route, $this->router->getRoutes()[$key][0]);
        }
        $this->assertCount(5, $this->router->getRoutes());
    }
    public function test_router_registers_routes_and_returns_route_instance(): void
    {
        $route = $this->router->get('/test', fn() => 'ok');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame($route, $this->router->getRoutes()['GET'][0]);
    }
    public function test_dispatch_executes_closure_and_returns_result(): void
    {
        $_SERVER['REQUEST_URI'] = '/hello/flipa';
        $request = Request::capture();
        $this->router->get('/hello/{name}', fn(string $name) => "Hello $name");
        $this->assertSame('Hello flipa',  $this->router->dispatch($request));
    }
    public function test_dispatch_resolves_controller_from_container_and_executes_method(): void
    {
        $_SERVER['REQUEST_URI'] = '/controller/2';
        $request = Request::capture();
        $this->router->get('/controller/{id}', [TestController::class, 'index']);
        $this->assertSame('Accessing a controller method with id 2.',  $this->router->dispatch($request));
    }
    public function test_dispatch_passes_dynamic_parameters_to_action(): void
    {
        $_SERVER['REQUEST_URI'] = '/users/32';
        $request = Request::capture();

        $this->router->get('/users/{id}', fn(string $id) => $id);

        $capturedId = $this->router->dispatch($request);
        $this->assertSame('32',  $capturedId);
    }
    public function test_dispatch_throws_not_found_exception_for_unregistered_uri(): void
    {
        $_SERVER['REQUEST_URI'] = '/does-not-exist';
        $request = Request::capture();

        $this->expectException(NotFoundException::class);

        $this->router->get('/exists', fn() => 'ok');
        $this->router->dispatch($request);
    }
    public function test_dispatch_throws_method_not_allowed_exception_with_allowed_methods(): void
    {
        $_SERVER['REQUEST_URI'] = '/users';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Request::capture();

        $this->router->get('/users', fn() => 'ok');

        try {
            $this->router->dispatch($request);
            $this->fail('Expected MethodNotAllowedException was not thrown.');
        } catch (MethodNotAllowedException $e) {
            $this->assertSame(['GET'], $e->getAllowedMethods());
        }
    }
}
