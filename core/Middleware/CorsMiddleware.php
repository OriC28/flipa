<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\Contracts\MiddlewareInterface;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Contracts\ResponseInterface;
use Core\Http\Response;

class CorsMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, \Closure $next): ResponseInterface
    {
        $CORSHeaders = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization, Accept'
        ];
        if ($request->getMethod() === 'OPTIONS') {
            return (new Response())->withHeaders($CORSHeaders)->withStatus(204);
        }
        $response = $next($request);
        return $response->withHeaders($CORSHeaders);
    }
}
