<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Config\Config;
use Core\Database\Database;
use Core\Events\Dispatcher;
use Core\Exceptions\Handler;
use Core\Foundation\Application;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Request;
use Core\Http\Route;
use Core\Http\Router;
use Core\Log\Logger;
use Core\Middleware\CorsMiddleware;
use Core\Middleware\StartSessionMiddleware;
use Core\Session\Session;
use Core\View\View;
use Tests\TestBase;

use Tests\Fixtures\Container\DeepDependencyA;
use Tests\Fixtures\Container\DeepDependencyB;
use Tests\Fixtures\Container\DeepDependencyC;

class ContainerApplicationIntegrationTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
    }
    protected function tearDown(): void
    {
        parent::tearDown();
    }
    public function test_application_registers_core_bindings_and_resolves_singleton_services(): void
    {
        $this->app->registerCoreBindings();
        $coreSinglentons = [
            Application::class,
            Config::class,
            Request::class,
            Router::class,
            Handler::class,
            Session::class,
            Logger::class,
            Database::class,
            Dispatcher::class,
            View::class
        ];

        foreach ($coreSinglentons as $singleton) {
            $this->assertSame(app($singleton), app($singleton));
        }

        $this->assertInstanceOf(CorsMiddleware::class, app(CorsMiddleware::class));
        $this->assertInstanceOf(StartSessionMiddleware::class, app(StartSessionMiddleware::class));

        $this->assertNotSame(app(CorsMiddleware::class), app(CorsMiddleware::class));
        $this->assertNotSame(app(StartSessionMiddleware::class), app(StartSessionMiddleware::class));
    }
    public function test_application_resolves_request_interface_to_concrete_request_instance(): void
    {
        $this->assertSame(app(RequestInterface::class), app(RequestInterface::class));
        $this->assertInstanceOf(Request::class, app(RequestInterface::class));
    }
    public function test_application_loads_routes_and_registers_them_in_router(): void
    {
        $tempBaseDir = sys_get_temp_dir() . '/flipa_routes_' . uniqid();
        mkdir($tempBaseDir . '/routes', 0777, true);

        $content = '<?php return function(\Core\Http\Router $router) { $router->get("/integration-test", fn() => "ok"); };';

        file_put_contents($tempBaseDir . '/routes/web.php', $content);

        $reflection = new \ReflectionProperty($this->app, 'basePath');
        $originalBasePath = $reflection->getValue($this->app);
        $reflection->setValue($this->app, $tempBaseDir);

        $reflectionRoute = new \ReflectionProperty($this->app, 'routePath');
        $originalRoutePath = $reflectionRoute->getValue($this->app);
        $reflectionRoute->setValue($this->app, 'routes/web.php');

        try {
            $this->app->registerCoreBindings();
            $this->app->loadRoutes();

            $router = $this->app->make(Router::class);
            $routes = $router->getRoutes();
            $paths = array_map(fn(Route $route) => $route->getPath(), $routes['GET']);

            $this->assertArrayHasKey('GET', $routes);
            $this->assertNotEmpty($routes['GET']);
            $this->assertContains('/integration-test', $paths);
        } finally {
            $reflection->setValue($this->app, $originalBasePath);
            $reflectionRoute->setValue($this->app, $originalRoutePath);
            unlink($tempBaseDir . '/routes/web.php');
            rmdir($tempBaseDir . '/routes');
            rmdir($tempBaseDir);
        }
    }
    public function test_container_resolves_deep_dependency_chain_through_auto_wiring(): void
    {
        // Without registering anything, the container must resolve A -> B -> C in a chain
        $instanceA = $this->app->make(DeepDependencyA::class);

        $this->assertInstanceOf(DeepDependencyA::class, $instanceA);
        $this->assertInstanceOf(DeepDependencyB::class, $instanceA->b);
        $this->assertInstanceOf(DeepDependencyC::class, $instanceA->b->c);
    }
    public function test_application_self_registers_as_singleton_in_constructor(): void
    {
        $this->assertSame($this->app, app(Application::class));
    }
}
