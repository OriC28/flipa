<?php

declare(strict_types=1);

namespace Core\Config;

class Config
{
    private array $items = [];
    public function __construct(protected string $configPath)
    {
        $this->load($this->configPath);
    }

    private function load(string $path): void
    {
        $configFiles = glob("$path/*.php");
        foreach ($configFiles as $file) {
            $filename = basename($file, ".php");
            $this->items[$filename] = require $file;
        }
    }

    public function get(string $key, mixed $default = null)
    {
        $keys = explode('.', $key);
        if (!isset($this->items[$keys[0]])) {
            return $default;
        }

        $current = $this->items[$keys[0]];
        foreach (array_slice($keys, 1) as $_key) {
            if (!isset($current[$_key])) {
                return $default;
            }
            $current = $current[$_key];
        }
        return $current;
    }
}
