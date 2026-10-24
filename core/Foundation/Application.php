<?php

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
    public function __construct()
    {
        $this->singleton(self::class, fn() => $this);
    }

    public function registerCoreBindings(): self
    {
        $this->bind(CorsMiddleware::class, fn() => new CorsMiddleware());
        $this->bind(StartSessionMiddleware::class, fn() => new StartSessionMiddleware($this));

        $this->singleton(Router::class, fn() => new Router($this));
        $this->singleton(Request::class, fn() => Request::capture());
        $this->singleton(RequestInterface::class, fn() => $this->make(Request::class));
        $this->singleton(Handler::class, fn() => new Handler());
        $this->singleton(Config::class, fn() => new Config(__DIR__ . '/../../config'));
        $this->singleton(Session::class, fn() => new Session());
        $this->singleton(Database::class, fn() => new Database($this->make(Config::class)));
        $this->singleton(Logger::class, fn() => new Logger(__DIR__ . '/../../storage/logs'));
        $this->singleton(Dispatcher::class, fn() => new Dispatcher());
        $this->singleton(View::class, fn() => new View([
            __DIR__ . '/../../resources/views',
            __DIR__ . '/../../core/Exceptions/views'
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

    public function loadRoutes(string $web): self
    {
        $loadRoutes = require $web;
        $loadRoutes($this->make(Router::class));
        return $this;
    }
}
