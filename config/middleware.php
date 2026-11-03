<?php

declare(strict_types=1);

use Core\Middleware\CorsMiddleware;
use Core\Middleware\CsrfMiddleware;
use Core\Middleware\SecurityHeadersMiddleware;
use Core\Middleware\StartSessionMiddleware;

return [
    CorsMiddleware::class,
    StartSessionMiddleware::class,
    CsrfMiddleware::class,
    SecurityHeadersMiddleware::class
];
