<?php

namespace App\Middlewares;

use Core\Http\Contracts\MiddlewareInterface;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Contracts\ResponseInterface;
use Core\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, \Closure $next): ResponseInterface
    {

        if (!$request->has('token')) {
            return new Response(body: "Unauthorized", code: 401);
        }

        return $next($request);
    }
}
