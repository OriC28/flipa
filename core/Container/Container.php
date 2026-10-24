<?php

declare(strict_types=1);

namespace Core\Container;

use Core\Container\Exceptions\BindingResolutionException;
use Core\Container\Exceptions\CircularDependencyException;
use Core\Container\Exceptions\ContainerInstanceException;

class Container
{
    private array $bindings = [];
    private array $instances = [];
    private array $buildStack = [];
    private static ?self $instance = null;

    public function bind(string $abstract, \Closure|string $concrete)
    {
        $concrete instanceof \Closure ?
            $this->bindings[$abstract] = $concrete
            :
            $this->bindings[$abstract] = fn() => new $concrete();
    }

    public function singleton(string $abstract, \Closure|string $concrete)
    {
        $this->bindings[$abstract] = function () use ($abstract, $concrete) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $concrete instanceof \Closure
                    ? $concrete()
                    : new $concrete();
            }
            return $this->instances[$abstract];
        };
    }

    private function resolveNamedType(\ReflectionNamedType $namedType, \ReflectionParameter $parameter)
    {
        $name = $namedType->getName();

        if ($namedType->isBuiltin()) {

            if (!isset($this->bindings[$name]) && !$parameter->isDefaultValueAvailable()) {
                throw new BindingResolutionException("Target class [$name] is not bindable in the container.");
            }

            return isset($this->bindings[$name])
                ? $this->bindings[$name]()
                : $parameter->getDefaultValue();
        }
        return $this->make($name);
    }

    private function resolveParameter(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $namedType) {
                try {
                    return $this->resolveNamedType($namedType, $parameter);
                } catch (\Throwable $th) {
                    continue;
                }
            }
            throw new BindingResolutionException("No type of union was resolvable.");
        }

        if ($type instanceof \ReflectionNamedType) {
            return $this->resolveNamedType($type, $parameter);
        }

        // Without a defined type, but with a default value
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new BindingResolutionException("Parameter without type or default value.");
    }

    public function make(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]();
        }

        $reflection = new \ReflectionClass($abstract);

        if (!$reflection->isInstantiable()) {
            throw new BindingResolutionException("Target [$abstract] is not instantiable.");
        }

        if (\in_array($abstract, $this->buildStack)) {
            throw new CircularDependencyException($abstract);
        }

        $this->buildStack[] = $abstract;

        try {
            $constructor = $reflection->getConstructor();
            if ($constructor === null) {
                return $reflection->newInstance();
            }
            $resolvedDependencies = [];
            $parameters = $constructor->getParameters();
            foreach ($parameters as $parameter) {
                $resolvedDependencies[] = $this->resolveParameter($parameter);
            }
            return $reflection->newInstanceArgs($resolvedDependencies);
        } finally {
            array_pop($this->buildStack);
        }
    }

    public function call(\Closure|array $callback, array $parameters = []): mixed
    {
        $reflection =  \is_array($callback)
            ? new \ReflectionMethod($callback[0], $callback[1])
            : $reflection = new \ReflectionFunction($callback);

        $resolvedDependencies = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            $resolvedDependencies[] = \array_key_exists($name, $parameters)
                ? $parameters[$name]
                : $this->resolveParameter($parameter);
        }

        return $callback(...$resolvedDependencies);
    }

    public static function setInstance(self $instance): void
    {
        if (self::$instance) {
            throw new ContainerInstanceException('An instance of the container already exists. Overwriting the instance is not permitted.');
        }
        self::$instance = $instance;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new ContainerInstanceException('You cannot obtain the container instance before initializing app.php.');
        }
        return self::$instance;
    }
}
