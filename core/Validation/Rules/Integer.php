<?php

declare(strict_types=1);

namespace Core\Validation\Rules;

use Core\Validation\Concerns\Optional;
use Core\Validation\Contracts\RuleInterface;

class Integer implements RuleInterface
{
    use Optional;

    private string $error = '';

    public function passes(string $attribute, mixed $value): bool
    {
        if ($this->isOptional($value)) return true;

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->error = "The $attribute field must be an integer.";
        }

        return empty($this->error);
    }

    public function message(): string
    {
        return $this->error;
    }
}
