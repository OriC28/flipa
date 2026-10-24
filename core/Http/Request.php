<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Http\Contracts\RequestInterface;

class Request implements RequestInterface
{
    private ?array $jsonCache = null;
    public function __construct(
        protected readonly string $method,
        protected readonly string $uri,
        protected readonly string $ip,
        protected readonly string $host,
        protected readonly array $post,
        protected readonly array $query,
        protected readonly array $cookies,
        protected readonly array $headers,
        protected readonly array $files,
    ) {}
    public static function capture(): self
    {
        $server = $_SERVER ?? [];
        return new self(
            method: $server['REQUEST_METHOD'] ?? 'GET',
            uri: parse_url($server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            ip: $server['REMOTE_ADDR'] ?? '127.0.0.1',
            host: $server['HTTP_HOST'] ?? 'localhost',
            post: $_POST,
            query: $_GET,
            cookies: $_COOKIE,
            headers: $server,
            files: $_FILES,
        );
    }
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
        return [...$this->query, ...$this->post, ...$this->json()];
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

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type') ?? '', 'application/json');
    }

    public function json(): array
    {
        if ($this->isJson()) {
            // In-memory cache to process the `php://input` stream only once.
            if ($this->jsonCache === null) {
                $rawData = file_get_contents('php://input');
                $this->jsonCache = json_decode($rawData, true) ?? [];
            }
            return $this->jsonCache;
        }
        return [];
    }
}
