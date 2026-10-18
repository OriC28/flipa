<?php

declare(strict_types=1);

use Core\Http\Contracts\RequestInterface;
use Core\Http\Response;
use Core\Http\Router;

$app = require __DIR__ . '/../bootstrap/app.php';

$request = $app->make(RequestInterface::class);
$router = $app->make(Router::class);

$response = (new \Core\Http\Pipeline($app))
    ->send($request)
    ->through([new \App\Middlewares\CorsMiddleware()])
    ->then(function ($request) use ($router) {
        $result = $router->dispatch($request);
        return new Response(body: $result);
    });

$response->send();
