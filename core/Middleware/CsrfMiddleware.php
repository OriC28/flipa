<?php

namespace Core\Middleware;

use Core\Http\Contracts\MiddlewareInterface;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Contracts\ResponseInterface;
use Core\Http\Exceptions\HttpException;

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, \Closure $next): ResponseInterface
    {
        $method = $request->getMethod();

        if (\in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        if (!\in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new HttpException("Method Not Allowed", 405);
        }

        $userToken = $request->post('_csrf_token') ?? $request->header('X-CSRF-Token');
        $sessionToken = csrf_token();

        if ($sessionToken === null || $userToken === null) {
            throw new HttpException('The CSRF token is missing.', 419);
        }

        if (!hash_equals($sessionToken, $userToken)) {
            throw new HttpException('The CSRF token provided does not match the session token.', 419);
        }

        return $next($request);
    }
}
