<?php

declare(strict_types=1);

namespace Core\Http\Exceptions;

class MiddlewareException extends \RuntimeException
{
    public function __construct(string $message, protected int $statusCode)
    {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
