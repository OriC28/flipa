<?php

use Core\Container\Container;
use Core\Log\Logger;
use Core\Log\LogLevel;
use Core\Session\Session;
use Core\View\View;

function app($abstract = null): mixed
{
    $app = Container::getInstance();
    if ($abstract === null) {
        return $app;
    }
    return $app->make($abstract);
}

function view(string $name, array $data = []): string
{
    return app(View::class)->render($name, $data);
}

function session(?string $key = null, mixed $value = null): mixed
{
    $session =  app(Session::class);
    $paramsCount  = func_num_args();

    if ($paramsCount === 0) {
        return $session;
    }
    return $paramsCount === 1 ? $session->get($key) : $session->put($key, $value);
}

function logger(?string $message = null, array $context = [], LogLevel|string $level = LogLevel::INFO): Logger
{
    $logger = app(Logger::class);

    if ($message === null) {
        return $logger;
    }

    $logger->log($level, $message, $context);

    return $logger;
}

function env(string $key, $default = null): mixed
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function e(string $var, bool $doubleEncode = true): string
{
    return htmlspecialchars(string: $var, double_encode: $doubleEncode);
}

function class_basename(string|object $class): string
{
    $class = is_object($class) ? get_class($class) : $class;

    return basename(str_replace('\\', '/', $class));
}
