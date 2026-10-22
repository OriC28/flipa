<?php

declare(strict_types=1);

namespace Core\Validation;

use Core\Validation\Contracts\RuleInterface;
use Core\Validation\Rules\Email;
use Core\Validation\Rules\Max;
use Core\Validation\Rules\Min;
use Core\Validation\Rules\Required;

class RuleFactory
{
    private static array $rulesMap = [
        'required' => Required::class,
        'min'      => Min::class,
        'max'      => Max::class,
        'email' => Email::class,
    ];

    public static function make(string $ruleName, array $params = []): RuleInterface
    {
        if (!isset(self::$rulesMap[$ruleName])) {
            throw new \Exception("Validation rule [$ruleName] is not supported.");
        }

        $ruleClass = self::$rulesMap[$ruleName];
        $reflection = new \ReflectionClass($ruleClass);

        $castedParams = self::castedParameters($reflection, $params);

        return $reflection->newInstanceArgs($castedParams);
    }

    private static function castedParameters(\ReflectionClass $reflection,  array $params): array
    {
        $constructor = $reflection->getConstructor();
        $castedParams = [];

        if ($constructor) {
            foreach ($constructor->getParameters() as $index => $param) {
                $value = $params[$index] ?? null;
                $type = $param->getType();

                $castedParams[] = ($type instanceof \ReflectionNamedType && $value !== null)
                    ? match ($type->getName()) {
                        'int'   => (int) $value,
                        'float' => (float) $value,
                        'bool'  => (bool) $value,
                        default => $value
                    }
                    : $value;
            }
        }
        return $castedParams;
    }
}
