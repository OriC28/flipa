<?php

declare(strict_types=1);

namespace Core\Http\Exceptions;

class OriginNotAllowedException extends HttpException
{
    public function __construct()
    {
        return parent::__construct('The origin of the request is not in the list of allowed domains.', 403);
    }
}
