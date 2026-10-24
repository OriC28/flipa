<?php

namespace Core\Middleware;

use Core\Http\Contracts\MiddlewareInterface;
use Core\Http\Contracts\ResponseInterface;
use Core\Http\Contracts\RequestInterface;

class StartSessionMiddleware implements MiddlewareInterface
{
    public function __construct() {}

    public function handle(RequestInterface $request, \Closure $next): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return $next($request);
    }
}
