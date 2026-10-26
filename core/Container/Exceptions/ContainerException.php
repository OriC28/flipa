<?php

declare(strict_types=1);

namespace Core\Container\Exceptions;

class ContainerException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
