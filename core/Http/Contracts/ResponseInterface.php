<?php

declare(strict_types=1);

namespace Core\Http\Contracts;

interface ResponseInterface
{
    public function withHeader(string $name, string $value): static;
    public function withHeaders(array $headers): static;
    public function withStatus(int $code): static;
    public function withBody(string|array $body): static;
    public function withCookie(string $name, string $value, int $expires = 0): static;
    public function asJson(): static;
    public function send(): void;
    public function sendCookies(): void;
}
