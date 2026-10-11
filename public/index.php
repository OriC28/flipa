<?php

declare(strict_types=1);

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Router;

require __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->safeLoad();
$dotenv->required("APP_ENV");

if ($_ENV["APP_ENV"] === "development") {
    $whoops = new \Whoops\Run();
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
    $whoops->register();
}

$request = new Request(
    method: $_SERVER['REQUEST_METHOD'],
    uri: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    ip: $_SERVER['REMOTE_ADDR'],
    host: $_SERVER['HTTP_HOST'],
    post: $_POST,
    query: $_GET,
    cookies: $_COOKIE,
    headers: $_SERVER,
    files: $_FILES,
);

$router = new Router();


$router->get('/', fn(Request $request) => "Flipa is running from '/'.");

try {
    $result = $router->dispatch($request);
    $response = new Response(body: $result);
    $response->send();
} catch (\Core\Http\Exceptions\HttpException $e) {
    $response = new Response(
        body: "{$e->getMessage()} Code: {$e->getStatusCode()}",
        code: $e->getStatusCode()
    );
    $response->send();
}
