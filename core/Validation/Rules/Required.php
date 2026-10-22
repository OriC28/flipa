<?php

declare(strict_types=1);

namespace Core\Validation\Rules;

use Core\Validation\Contracts\RuleInterface;

class Required implements RuleInterface
{
    public string $error = '';
    public function passes(string $attribute, mixed $value): bool
    {
        if (!isset($value) || $value === '') {
            $this->error = "The $attribute field is required.";
        }
        return empty($this->error);
    }
    public function message(): string
    {
        return $this->error;
    }
}
