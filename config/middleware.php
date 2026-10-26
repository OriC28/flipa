<?php

declare(strict_types=1);

use Core\Middleware\CorsMiddleware;
use Core\Middleware\StartSessionMiddleware;

return [
    CorsMiddleware::class,
    StartSessionMiddleware::class
];
