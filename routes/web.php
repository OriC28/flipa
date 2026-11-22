<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use Core\Http\Router;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register the routes for your application. Routes are
| loaded automatically by the application bootstrap. Both closures and
| controller action tuples ([Controller::class, 'method']) are supported.
|
*/

return function (Router $router): void {
    $router->get('/', fn(): string => view('index'));

    // Example routes. Remove them when starting your own application.
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->post('/dashboard', [DashboardController::class, 'store']);
};
