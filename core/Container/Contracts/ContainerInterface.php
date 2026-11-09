<?php

declare(strict_types=1);

namespace Core\Container\Contracts;

interface ContainerInterface
{
    public function make(string $abstract): mixed;
    public function singleton(string $abstract, \Closure|string $concrete): void;
    public function bind(string $abstract, \Closure|string $concrete): void;
    public function has(string $abstract): bool;
    public function call(\Closure|array $callback, array $parameters = []): mixed;
    public static function setInstance(ContainerInterface $instance): void;
    public static function getInstance(): static;
    public static function clearInstance(): void;
}
