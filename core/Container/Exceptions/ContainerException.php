<?php

declare(strict_types=1);

namespace Core\Container\Exceptions;

class ContainerException extends \RuntimeException
{
    public function __construct(string $message, protected int $statusCode = 0)
    {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
