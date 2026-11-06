<?php

declare(strict_types=1);

use Core\Foundation\Application;
use Core\Container\Container;
use Dotenv\Dotenv;

require __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->safeLoad();
$dotenv->required("APP_ENV");

$app = (new Application(dirname(__DIR__)))
    ->registerCoreBindings()
    ->loadRoutes();

$isPHPUnit = defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__');

if (!$isPHPUnit) {
    $app->registerExceptionHandling();

    if (env('APP_ENV') === "development") {
        $whoops = new \Whoops\Run();
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
        $whoops->register();
    }
}

Container::setInstance($app);

return $app;
