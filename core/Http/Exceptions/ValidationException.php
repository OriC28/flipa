<?php

declare(strict_types=1);

namespace Core\Http\Exceptions;

class ValidationException extends HttpException
{
    public function __construct(public array $errors = [])
    {
        parent::__construct("422 Unprocessable Entity.", 422);
    }
}
