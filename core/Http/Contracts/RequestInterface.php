<?php

declare(strict_types=1);

namespace Core\Http\Contracts;

interface RequestInterface
{
    public function getMethod(): string;
    public function getUri(): string;
    public function query(string $param, ?string $default = null): ?string;
    public function cookie(string $name, ?string $default = null): ?string;
    public function post(string $param, ?string $default = null): ?string;
    public function header(string $name, ?string $default = null): ?string;
    public function has(string $key): bool;
    public function all(): array;
    public function file(string $file_name, ?string $default = null): ?string;
    public function ip(): string;
    public function host(): string;
}
