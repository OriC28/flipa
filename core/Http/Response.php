<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Http\Contracts\ResponseInterface;

class Response implements ResponseInterface
{
    public function __construct(
        protected int $code = 200,
        protected string|array $body = "",
        protected array $headers = [],
        protected array $cookies = [],
    ) {}

    public function withHeader(string $name, string $value): static
    {
        $new = clone $this;
        $new->headers[ucwords($name, '-')] = $value;
        return $new;
    }

    public function withHeaders(array $headers): static
    {
        $new = clone $this;
        foreach ($headers as $name => $value) {
            $new->headers[ucwords($name, '-')] = $value;
        }
        return $new;
    }

    public function withStatus(int $code): static
    {
        $new = clone $this;
        $new->code = $code;
        return $new;
    }

    public function withBody(string|array $body): static
    {
        $new = clone $this;
        $new->body = \is_array($body) ? json_encode($body, JSON_PRETTY_PRINT) : $body;
        return $new;
    }

    public function withCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false,
        string $samesite = 'Lax'
    ): static {
        $new  = clone $this;
        $new->cookies[$name] = [
            'value' => $value,
            'options' => [
                'expires' => $expires,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite
            ]
        ];
        return $new;
    }

    public function asJson(): static
    {
        $new = clone $this;

        if (!isset($new->headers['Content-Type'])) {
            return $new->withHeader('Content-Type', 'application/json');
        }
        return $new;
    }

    public function send(): void
    {
        $this->sendCookies();

        http_response_code($this->code);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->body;
    }

    public function sendCookies(): void
    {
        if ($this->cookies) {
            foreach ($this->cookies as $name => $value) {
                setcookie($name, $value['value'], $value['options']);
            }
        }
    }
}
