<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Container\Container;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Exceptions\MethodNotAllowedException;
use Core\Http\Exceptions\NotFoundException;

class Router
{
    private array $routes = [];

    public function __construct(private Container $app) {}

    private function addRoute(string $method, string $path, \Closure|array $action): Route
    {
        $this->routes[$method] ??= [];
        $route = new Route($path, $method, $action);
        $this->routes[$method][] = $route;
        return $route;
    }

    public function get(string $path, \Closure|array $action): Route
    {
        return $this->addRoute('GET', $path, $action);
    }

    public function post(string $path, \Closure|array $action): Route
    {
        return $this->addRoute('POST', $path, $action);
    }

    public function put(string $path, \Closure|array $action): Route
    {
        return $this->addRoute('PUT', $path, $action);
    }

    public function patch(string $path, \Closure|array $action): Route
    {
        return $this->addRoute('PATCH', $path, $action);
    }

    public function delete(string $path, \Closure|array $action): Route
    {
        return $this->addRoute('DELETE', $path, $action);
    }

    public function dispatch(RequestInterface $request): mixed
    {
        $currentHttpMethod = $request->getMethod();
        $currentUri = $request->getUri();

        if (isset($this->routes[$currentHttpMethod])) {
            foreach ($this->routes[$currentHttpMethod] as $route) {
                $params = $route->match($currentUri);
                if ($params !== null) {
                    if (\is_array($route->getAction())) {
                        [$controllerClass, $method] = $route->getAction();
                        $controller = $this->app->make($controllerClass);
                        return $this->app->call([$controller, $method], $params);
                    }
                    return $this->app->call($route->getAction(), $params);
                }
            }
        }

        $allowedMethods = [];

        foreach ($this->routes as $httpMethod => $routes) {
            if ($httpMethod === $currentHttpMethod) {
                continue;
            }

            foreach ($routes as $route) {
                if ($route->match($currentUri) !== null) {
                    if (!\in_array($route->method, $allowedMethods)) {
                        $allowedMethods[] = $route->method;
                    }
                }
            }
        }
        if (!$allowedMethods) {
            throw new NotFoundException();
        }

        throw new MethodNotAllowedException(allowedMethods: $allowedMethods);
    }
}
