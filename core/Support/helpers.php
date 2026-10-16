<?php

use Core\Session\Session;
use Core\View\View;

function app($abstract = null): mixed
{
    $app = $GLOBALS["app"];
    if ($abstract === null) {
        return $app;
    }
    return $app->make($abstract);
}

function view(string $name, array $data = []): string
{
    return app(View::class)->render($name, $data);
}

function session(?string $key = null, mixed $value = null)
{
    $session =  app(Session::class);
    $paramsCount  = func_num_args();

    if ($paramsCount === 0) {
        return $session;
    }
    return func_num_args() === 1 ? $session->get($key) : $session->put($key, $value);
}

function env(string $key, $default = null): mixed
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function e(string $var, bool $doubleEncode = true): string
{
    return htmlspecialchars(string: $var, double_encode: $doubleEncode);
}
