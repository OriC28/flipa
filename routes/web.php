<?php

use App\Controllers\DashboardController;
use Core\Http\Request;
use Core\Http\Router;

return function (Router $router): void {
    $router->get('/', fn() => 'Probando desde web.php');

    $router->get(
        '/users/{id}',
        fn(Request $request, string $id) => "Hola usuario número: $id"
    );

    $router->get('/dashboard', [DashboardController::class, 'index']);

    $router->post('/dashboard', [DashboardController::class, 'store']);
};
