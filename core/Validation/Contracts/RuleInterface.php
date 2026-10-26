<?php

namespace Core\Validation\Contracts;

interface RuleInterface
{
    public function passes(string $attribute, mixed $value): bool;

    public function message(): string;
}
