<?php

use Core\Container\Container;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Request;
use Core\Http\Router;
use Dotenv\Dotenv;

require __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->safeLoad();
$dotenv->required("APP_ENV");

if ($_ENV["APP_ENV"] === "development") {
    $whoops = new \Whoops\Run();
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
    $whoops->register();
}

$app = new Container();

$app->bind(
    RequestInterface::class,
    fn() => new Request(
        method: $_SERVER['REQUEST_METHOD'],
        uri: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        ip: $_SERVER['REMOTE_ADDR'],
        host: $_SERVER['HTTP_HOST'],
        post: $_POST,
        query: $_GET,
        cookies: $_COOKIE,
        headers: $_SERVER,
        files: $_FILES,
    )
);

$app->singleton(Router::class, fn() => new Router($app));

$router = $app->make(Router::class);

$loadRoutes = require __DIR__ . '/../routes/web.php';
$loadRoutes($router);

return $app;
