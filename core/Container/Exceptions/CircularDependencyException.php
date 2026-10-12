<?php

declare(strict_types=1);

namespace Core\Container\Exceptions;

class CircularDependencyException extends ContainerException
{
    public function __construct(string $target)
    {
        parent::__construct("A circular dependency involving the class [$target] was detected.");
    }
}
