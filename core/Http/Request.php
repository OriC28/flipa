<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Http\Contracts\RequestInterface;

class Request implements RequestInterface
{
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly string $ip,
        private readonly string $host,
        private readonly array $post,
        private readonly array $query,
        private readonly array $cookies,
        private readonly array $headers,
        private readonly array $files,
    ) {}

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function query(string $param, ?string $default = null): ?string
    {
        return $this->query[$param] ?? $default;
    }

    public function cookie(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }

    public function post(string $param, ?string $default = null): ?string
    {
        return $this->post[$param] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $name = str_replace('-', '_', strtoupper($name));

        if ($name === 'CONTENT_TYPE') {
            return $this->headers[$name] ?? $default;
        }

        return  $this->headers["HTTP_$name"] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->query[$key]) || isset($this->post[$key]);
    }

    public function all(): array
    {
        return [...$this->query, ...$this->post];
    }

    public function file(string $file_name, ?string $default = null): ?string
    {
        return $this->files[$file_name] ?? $default;
    }

    public function ip(): string
    {
        return $this->ip;
    }

    public function host(): string
    {
        return $this->host;
    }
}
