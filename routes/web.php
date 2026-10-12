<?php

use Core\Http\Request;
use Core\Http\Router;

return function (Router $router): void {
    $router->get('/', fn() => 'Probando desde web.php');

    $router->get(
        '/users/{id}',
        fn(Request $request, string $id) => "Hola usuario número: $id"
    );
};
