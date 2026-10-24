<?php

namespace Core\Middleware;

use Core\Container\Container;
use Core\Http\Contracts\MiddlewareInterface;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Contracts\ResponseInterface;
use Core\Session\Session;

class StartSessionMiddleware implements MiddlewareInterface
{
    public function __construct(private Container $app) {}

    public function handle(RequestInterface $request, \Closure $next): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->app->singleton(Session::class, fn() => new Session());
        return $next($request);
    }
}
