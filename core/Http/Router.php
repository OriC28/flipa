<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Http\Contracts\RequestInterface;
use Core\Http\Exceptions\MethodNotAllowedException;
use Core\Http\Exceptions\NotFoundException;

class Router
{
    private array $routes = [];

    private function addRoute(string $method, string $path, \Closure $handler): Route
    {
        $this->routes[$method] ??= [];
        $route = new Route($path, $method, $handler);
        $this->routes[$method][] = $route;
        return $route;
    }

    public function get(string $path, \Closure $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, \Closure $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, \Closure $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, \Closure $handler): Route
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, \Closure $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function dispatch(RequestInterface $request): mixed
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                $params = $route->match($uri);
                if ($params !== null) {
                    return $route->getHandler()($request, ...$params);
                }
            }
        }

        $allowed_methods = [];

        foreach ($this->routes as $http_method => $routes) {
            if ($http_method === $method) {
                continue;
            }

            foreach ($routes as $route) {
                if ($route->match($uri) !== null) {
                    if (!\in_array($route->method, $allowed_methods)) {
                        $allowed_methods[] = $route->method;
                    }
                }
            }
        }
        if (!$allowed_methods) {
            throw new NotFoundException();
        }

        throw new MethodNotAllowedException(allowedMethods: $allowed_methods);
    }
}
