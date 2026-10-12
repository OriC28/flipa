<?php

declare(strict_types=1);

use Core\Http\Contracts\RequestInterface;
use Core\Http\Response;
use Core\Http\Router;

$app = require __DIR__ . '/../bootstrap/app.php';

$request = $app->make(RequestInterface::class);
$router = $app->make(Router::class);

try {
    $result = $router->dispatch($request);

    $response = new Response(body: $result);
    $response->send();
} catch (\Core\Http\Exceptions\HttpException $th) {
    $response = new Response(
        body: "{$th->getMessage()} Code: {$th->getStatusCode()}",
        code: $th->getStatusCode()
    );

    if ($th instanceof \Core\Http\Exceptions\MethodNotAllowedException) {
        $allowed_methods_string = implode(', ', $th->getAllowedMethods());
        $response = $response->withHeader('Allow', $allowed_methods_string);
    }

    $response->send();
}
