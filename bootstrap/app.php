<?php

use Core\Config\Config;
use Core\Container\Container;
use Core\Database\Database;
use Core\Events\Dispatcher;
use Core\Exceptions\Handler;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Request;
use Core\Http\Router;
use Core\Log\Logger;
use Core\Session\Session;
use Core\View\View;
use Dotenv\Dotenv;

require __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->safeLoad();
$dotenv->required("APP_ENV");

if (env('APP_ENV') === "development") {
    $whoops = new \Whoops\Run();
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
    $whoops->register();
}

$app = new Container();

$app->singleton(
    Request::class,
    fn() => Request::capture()
);

$app->singleton(
    RequestInterface::class,
    function () use ($app) {
        return $app->make(Request::class);
    }
);

$app->singleton(Router::class, fn() => new Router($app));
$app->singleton(Handler::class, fn() => new Handler());
$app->singleton(Config::class, fn() => new Config(__DIR__ . '/../config'));
$app->singleton(Session::class, fn() => new Session());
$app->singleton(Database::class, fn() => new Database($app->make(Config::class)));
$app->singleton(Logger::class, fn() => new Logger(__DIR__ . '/../storage/logs'));
$app->singleton(Dispatcher::class, fn() => new Dispatcher());
$app->singleton(View::class, fn() => new View([
    __DIR__ . '/../resources/views',
    __DIR__ . '/../core/Exceptions/views'
]));

set_exception_handler(function (\Throwable $exception) use ($app) {
    $handler = $app->make(Handler::class);
    $response = $handler->render($app->make(RequestInterface::class), $exception);
    $response->send();
});

$router = $app->make(Router::class);

$loadRoutes = require __DIR__ . '/../routes/web.php';
$loadRoutes($router);

Container::setInstance($app);

return $app;
