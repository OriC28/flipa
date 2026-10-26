<?php

declare(strict_types=1);

namespace Core\Validation\Contracts;

interface RuleInterface
{
    public function passes(string $attribute, mixed $value): bool;

    public function message(): string;
}
