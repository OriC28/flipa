<?php

declare(strict_types=1);

namespace Core\Validation\Rules;

use Core\Validation\Concerns\Optional;
use Core\Validation\Contracts\RuleInterface;

class Alpha implements RuleInterface
{
    use Optional;

    private string $error = '';

    public function passes(string $attribute, mixed $value): bool
    {
        if ($this->isOptional($value)) return true;

        if (!ctype_alpha((string) $value)) {
            $this->error = "The $attribute field must contain only letters.";
        }

        return empty($this->error);
    }

    public function message(): string
    {
        return $this->error;
    }
}
