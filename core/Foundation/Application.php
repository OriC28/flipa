<?php

declare(strict_types=1);

namespace Core\Foundation;

use Core\Middleware\StartSessionMiddleware;
use Core\Middleware\CorsMiddleware;

use Core\Http\Contracts\RequestInterface;
use Core\Http\Request;
use Core\Http\Router;

use Core\Container\Container;
use Core\Exceptions\Handler;
use Core\Database\Database;
use Core\Events\Dispatcher;

use Core\Session\Session;
use Core\Config\Config;

use Core\Log\Logger;
use Core\View\View;

class Application extends Container
{
    protected string $basePath;
    protected string $routePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->routePath = 'routes' . DIRECTORY_SEPARATOR . 'web.php';
        $this->singleton(self::class, fn() => $this);
    }

    public function basePath(string $path = ''): string
    {
        return $path === ''
            ? $this->basePath
            : join(DIRECTORY_SEPARATOR, [$this->basePath, ltrim($path, '/\\')]);
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function databasePath(string $path = ''): string
    {
        return $this->basePath('database' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function appPath(string $path = ''): string
    {
        return $this->basePath('app' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function registerCoreBindings(): self
    {
        $this->bind(CorsMiddleware::class, fn() => new CorsMiddleware());
        $this->bind(StartSessionMiddleware::class, fn() => new StartSessionMiddleware());

        $this->singleton(Router::class, fn() => new Router($this));
        $this->singleton(Request::class, fn() => Request::capture());
        $this->singleton(RequestInterface::class, fn() => $this->make(Request::class));
        $this->singleton(Handler::class, fn() => new Handler());
        $this->singleton(Config::class, fn() => new Config($this->configPath()));
        $this->singleton(Session::class, fn() => new Session());
        $this->singleton(Database::class, fn() => new Database($this->make(Config::class)));
        $this->singleton(Logger::class, fn() => new Logger($this->basePath('storage/logs'), 'flipa.log'));
        $this->singleton(Dispatcher::class, fn() => new Dispatcher($this));
        $this->singleton(View::class, fn() => new View([
            $this->resourcePath('views'),
            $this->basePath('core/Exceptions/views')
        ]));

        return $this;
    }

    public function registerExceptionHandling(): self
    {
        set_exception_handler(function (\Throwable $exception) {
            $handler = $this->make(Handler::class);
            $response = $handler->render($this->make(RequestInterface::class), $exception);
            $response->send();
        });
        return $this;
    }

    public function loadRoutes(): self
    {
        $loadRoutes = require $this->basePath($this->routePath);
        $loadRoutes($this->make(Router::class));
        return $this;
    }
}
