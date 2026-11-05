<?php

declare(strict_types=1);

namespace Core\Validation\Rules;

use Core\Validation\Concerns\Optional;
use Core\Validation\Contracts\RuleInterface;

class Boolean implements RuleInterface
{
    use Optional;

    private string $error = '';

    public function passes(string $attribute, mixed $value): bool
    {
        if ($this->isOptional($value)) return true;

        $acceptedValues = [true, false, 0, 1, '0', '1', 'true', 'false'];

        if (!\in_array($value, $acceptedValues, true)) {
            $this->error = "The $attribute field must be true or false.";
        }

        return empty($this->error);
    }

    public function message(): string
    {
        return $this->error;
    }
}
