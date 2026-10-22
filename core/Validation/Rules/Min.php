<?php

declare(strict_types=1);

namespace Core\Validation\Rules;

use Core\Validation\Concerns\Optional;
use Core\Validation\Contracts\RuleInterface;

class Min implements RuleInterface
{
    use Optional;
    public string $error = '';
    public function __construct(public int $limit = 8) {}
    public function passes(string $attribute, mixed $value): bool
    {
        if ($this->isOptional($value)) return true;

        if (mb_strlen((string) $value) < $this->limit) {
            $this->error = "The $attribute must have a minimum of {$this->limit} characters.";
        }
        return empty($this->error);
    }
    public function message(): string
    {
        return $this->error;
    }
}
