<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Config\Config;
use Core\Container\Container;
use Core\Foundation\Application;
use Core\Http\Request;
use Core\Http\Router;
use Core\View\View;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    protected Application $app;
    protected string $fakeBasePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeBasePath = '/var/www/flipa';
        $this->app = new Application($this->fakeBasePath);
        Container::setInstance($this->app);
    }
    protected function tearDown(): void
    {
        Container::clearInstance();
        parent::tearDown();
    }
    public function test_base_path_returns_root_directory_when_empty(): void
    {
        $this->assertSame($this->fakeBasePath, $this->app->basePath());
    }
    public function test_base_path_appends_relative_path_with_directory_separator(): void
    {
        $expected = $this->fakeBasePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Models';
        $this->assertSame($expected, $this->app->basePath('app/Models'));
    }
    public function test_base_path_strips_leading_slashes_from_relative_path(): void
    {
        $expected = $this->fakeBasePath . DIRECTORY_SEPARATOR . 'config';
        // When a route with a leading slash is passed, it should be ignored
        $this->assertSame($expected, $this->app->basePath('/config'));
    }
    public function test_config_path_resolves_to_config_directory(): void
    {
        $expected = $this->fakeBasePath . DIRECTORY_SEPARATOR . 'config';
        $this->assertSame($expected, $this->app->configPath());

        $expectedWithFile = $expected . DIRECTORY_SEPARATOR . 'app.php';
        $this->assertSame($expectedWithFile, $this->app->configPath('app.php'));
    }
    public function test_storage_path_resolves_to_storage_directory(): void
    {
        $expected = $this->fakeBasePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        $this->assertSame($expected, $this->app->storagePath('logs'));
    }
    public function test_database_path_resolves_to_database_directory(): void
    {
        $expected = $this->fakeBasePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        $this->assertSame($expected, $this->app->databasePath('migrations'));
    }
    public function test_resource_path_resolves_to_resources_directory(): void
    {
        $expected = $this->fakeBasePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';
        $this->assertSame($expected, $this->app->resourcePath('views'));
    }
    public function test_app_path_resolves_to_app_directory(): void
    {
        $expected = $this->fakeBasePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http';
        $this->assertSame($expected, $this->app->appPath('Http'));
    }
    public function test_application_registers_itself_as_singleton_in_container(): void
    {
        $this->assertSame($this->app, $this->app->make(Application::class));
    }
    public function test_register_core_bindings_registers_router_and_request_as_singletons(): void
    {
        $this->app->registerCoreBindings();

        $this->assertTrue($this->app->has(Router::class));
        $this->assertTrue($this->app->has(Request::class));

        $router1 = $this->app->make(Router::class);
        $router2 = $this->app->make(Router::class);
        $this->assertSame($router1, $router2);
    }
    public function test_register_core_bindings_registers_config_with_correct_config_path(): void
    {
        $this->app->registerCoreBindings();

        $this->assertTrue($this->app->has(Config::class));

        $config1 = $this->app->make(Config::class);
        $config2 = $this->app->make(Config::class);
        $this->assertSame($config1, $config2);
    }
    public function test_register_core_bindings_registers_view_with_resource_and_exception_paths(): void
    {
        $this->app->registerCoreBindings();

        $this->assertTrue($this->app->has(View::class));

        $view1 = $this->app->make(View::class);
        $view2 = $this->app->make(View::class);
        $this->assertSame($view1, $view2);
    }
    public function test_register_exception_handling_sets_global_exception_handler(): void
    {
        $result = $this->app->registerExceptionHandling();
        $this->assertInstanceOf(Application::class, $result);

        $newHandler = set_exception_handler(function () {});

        $this->assertIsCallable($newHandler);

        restore_exception_handler();
        restore_exception_handler();
    }
    public function test_load_routes_executes_web_routes_file_and_injects_router_instance(): void
    {
        $tempBaseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'flipa_test_' . uniqid();
        $tempRouteDir = $tempBaseDir . DIRECTORY_SEPARATOR . 'routes';

        mkdir($tempRouteDir, 0777, true);

        $tempRouteFile = $tempRouteDir . DIRECTORY_SEPARATOR . 'web.php';
        $content = '<?php return function(\Core\Http\Router $router) { $router->get("/test-temp", fn() => "ok"); };';
        file_put_contents($tempRouteFile, $content);

        try {
            $reflectionBase = new \ReflectionProperty($this->app, 'basePath');
            $originalBasePath = $reflectionBase->getValue($this->app);
            $reflectionBase->setValue($this->app, $tempBaseDir);

            $reflectionRoute = new \ReflectionProperty($this->app, 'routePath');
            $originalRoutePath = $reflectionRoute->getValue($this->app);
            $reflectionRoute->setValue($this->app, 'routes/web.php');

            $this->app->singleton(Router::class, fn() => new Router($this->app));

            $result = $this->app->loadRoutes();
            $this->assertInstanceOf(Application::class, $result);

            $router = $this->app->make(Router::class);
            $this->assertArrayHasKey('GET', $router->getRoutes());
        } finally {
            if (file_exists($tempRouteFile)) {
                unlink($tempRouteFile);
            }
            if (is_dir($tempRouteDir)) {
                rmdir($tempRouteDir);
            }
            if (is_dir($tempBaseDir)) {
                rmdir($tempBaseDir);
            }

            $reflectionBase->setValue($this->app, $originalBasePath);
            $reflectionRoute->setValue($this->app, $originalRoutePath);
        }
    }
}
