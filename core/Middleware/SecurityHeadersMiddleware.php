<?php

namespace Core\Middleware;

use Core\Http\Contracts\MiddlewareInterface;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Contracts\ResponseInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, \Closure $next): ResponseInterface
    {
        $headers = [
            'Content-Security-Policy' => "default-src 'self'",
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];
        header_remove('X-Powered-By');
        $response = $next($request);
        return $response->withHeaders($headers);
    }
}
