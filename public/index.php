<?php

declare(strict_types=1);

use Core\Container\Container;
use Core\Http\Contracts\RequestInterface;
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

class Test1 {}

class Service
{

    public string $name = 'Servicio';
    public function __construct(Test1 $test1) {}
}

class Test2
{
    public function __construct(public Test1 $test1, public string $nameTest, public Service $service) {}
}

$app = new Container();

try {
    $app->bind('string', fn() => "Probando desde index.php");
    $test = $app->make(Test2::class);
    $response = new Response();
    $response->withCookie('culo', 'coki', 900)->withHeader('Content-Type', 'html/text')->withStatus(500)->send();
    //print_r($test->test1);
} catch (\Exception $e) {
    echo $e->getMessage();
}
