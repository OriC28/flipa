<?php

declare(strict_types=1);

namespace Core\Http\Contracts;

interface ResponseInterface
{
    public function withHeader(string $name, string $value): self;
    public function withStatus(int $code): self;
    public function withBody(string|array $body): self;
    public function withCookie(string $name, string $value, int $expires = 0): self;
    public function asJson(): void;
    public function send(): void;
}
