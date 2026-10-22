<?php

declare(strict_types=1);

namespace Core\Validation\Concerns;

trait Optional
{
    public function isOptional(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
