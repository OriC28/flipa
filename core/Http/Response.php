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

    public function withHeader(string $name, string $value): Response
    {
        $new = clone $this;
        $new->headers[ucwords($name, '-')] = $value;
        return $new;
    }

    public function withHeaders(array $headers): Response
    {
        $new = clone $this;
        foreach ($headers as $name => $value) {
            $new->headers[ucwords($name, '-')] = $value;
        }
        return $new;
    }

    public function withStatus(int $code): Response
    {
        $new = clone $this;
        $new->code = $code;
        return $new;
    }

    public function withBody(string|array $body): Response
    {
        $new = clone $this;
        $new->body = \is_array($body) ? json_encode($body, JSON_PRETTY_PRINT) : $body;
        return $new;
    }

    public function withCookie(string $name, string $value, int $expires = 0): Response
    {
        $new  = clone $this;
        $new->cookies[$name] = ['value' => $value, 'expires' => $expires];
        return $new;
    }

    public function asJson(): void
    {
        if ($this->cookies) {
            foreach ($this->cookies as $name => $value) {
                setcookie($name, $value['value'], $value['expires']);
            }
        }
        http_response_code($this->code);
        if (!isset($this->headers["Content-Type"])) {
            $this->headers["Content-Type"] = "application/json";
        }
        header("Content-Type: " . $this->headers["Content-Type"]);
        echo $this->body;
    }

    public function send(): void
    {
        if ($this->cookies) {
            foreach ($this->cookies as $name => $value) {
                setcookie($name, $value['value'], $value['expires']);
            }
        }
        http_response_code($this->code);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->body;
    }
}
