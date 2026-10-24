<?php

use Core\Middleware\CorsMiddleware;
use Core\Middleware\StartSessionMiddleware;

return [
    CorsMiddleware::class,
    StartSessionMiddleware::class
];
