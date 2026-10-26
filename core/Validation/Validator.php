<?php

declare(strict_types=1);

namespace Core\Validation;

class Validator
{
    private array $errors = [];
    public function __construct(protected array $data, protected array $rules) {}

    public function fails(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            foreach ($rulesArray as $rule) {
                $parameters = explode(':', $rule);
                $ruleName = $parameters[0];
                $params = \array_slice($parameters, 1);

                $factory = RuleFactory::make($ruleName, $params);

                if (!$factory->passes($field, $this->data[$field] ?? null)) {
                    $this->errors[$field][] = $factory->message();
                }

                if (!isset($this->data[$field]) || $this->data[$field] === '') {
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
