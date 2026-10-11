<?php

declare(strict_types=1);

namespace Core\Http\Exceptions;

class MethodNotAllowedException extends HttpException
{
    public function __construct(string $message = "Method Not Allowed.", private $allowedMethods = [])
    {
        parent::__construct($message, 405);
        $this->allowedMethods = $allowedMethods;
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
