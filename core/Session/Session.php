<?php

declare(strict_types=1);

namespace Core\Session;

class Session
{
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }
    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function all(): array
    {
        return $_SESSION;
    }

    public function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function flash(string $key, string $message, FlashType $type): void
    {
        $_SESSION['_flash'][$key] = ['message' => $message, 'type' => $type];
    }

    public function getFlash(string $key): array
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return [];
        }
        $flash_content = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);

        return $flash_content;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
