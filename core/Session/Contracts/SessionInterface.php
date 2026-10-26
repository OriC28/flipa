<?php

declare(strict_types=1);

namespace Core\Session\Contracts;

use Core\Session\FlashType;

interface SessionInterface
{
    public function get(string $key): mixed;
    public function put(string $key, mixed $value): void;
    public function has(string $key): bool;

    public function all(): array;

    public function destroy(): void;

    public function flash(string $key, string $message, FlashType $type): void;

    public function getFlash(string $key): array;

    public function forget(string $key): void;
}
