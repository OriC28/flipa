<?php

use Core\Foundation\Application;
use Core\Container\Container;
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

$app = (new Application())
    ->registerCoreBindings()
    ->registerExceptionHandling()
    ->loadRoutes(web: __DIR__ . '/../routes/web.php');

Container::setInstance($app);

return $app;
