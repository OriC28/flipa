<?php

declare(strict_types=1);

namespace Core\Validation;

class Validator
{
    private array $errors = [];
    public function __construct(protected array $data, protected array $rules) {}

    public function fails(): mixed
    {
        foreach ($this->rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            foreach ($rulesArray as $rule) {
                $parameters = explode(':', $rule);
                $ruleName = $parameters[0];
                $param = $parameters[1] ?? null;

                if (!isset($this->data[$field])) {
                    $this->errors[$field][] = "The $field field is required.";
                    continue 2;
                }

                if (!isset($this->data[$field])) {
                    continue 2;
                }

                $result =  match ($ruleName) {
                    'min' => mb_strlen($this->data[$field]) >= $param,
                    'max' => mb_strlen($this->data[$field]) <= $param,
                    default => true
                };

                if ($result === false) {
                    $this->errors[$field][] = "The $field must have a $ruleName of $param characters.";
                    continue 2;
                }
            }
        }

        return !empty($this->errors);
    }
    public function errors(): array
    {
        return $this->errors;
    }
}
