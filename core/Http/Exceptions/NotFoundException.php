<?php

declare(strict_types=1);

namespace Core\Http\Exceptions;

class NotFoundException extends HttpException
{
    public function __construct(string $message = "Resource Not Found.")
    {
        parent::__construct($message, 404);
    }
}
