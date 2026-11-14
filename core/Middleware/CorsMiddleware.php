<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\Contracts\MiddlewareInterface;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Contracts\ResponseInterface;
use Core\Http\Exceptions\OriginNotAllowedException;
use Core\Http\Response;

class CorsMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, \Closure $next): ResponseInterface
    {
        $corsConfig = require config_path('cors.php');

        if ($request->header('Origin') === null) {
            return $next($request);
        }

        if (!\in_array($request->header('Origin'), $corsConfig['allowed_origins'])) {
            throw new OriginNotAllowedException();
        }

        $CORSHeaders = [
            'Access-Control-Allow-Origin' => $request->header('Origin'),
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization, Accept',
            'Access-Control-Allow-Credentials' => 'true',
        ];
        if ($request->getMethod() === 'OPTIONS') {
            return (new Response())->withHeaders($CORSHeaders)->withStatus(204);
        }
        $response = $next($request);
        return $response->withHeaders($CORSHeaders);
    }
}
