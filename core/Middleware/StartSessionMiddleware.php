<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\Contracts\MiddlewareInterface;
use Core\Http\Contracts\ResponseInterface;
use Core\Http\Contracts\RequestInterface;

class StartSessionMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, \Closure $next): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure'   => isHttps(),
                'cookie_samesite' => 'Lax',
            ]);
            if (!isset($_SESSION['_csrf_token'])) {
                $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            }
        }
        return $next($request);
    }
}
