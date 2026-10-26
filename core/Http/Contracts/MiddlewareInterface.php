<?php

declare(strict_types=1);

namespace Core\Http\Contracts;

interface MiddlewareInterface
{
    public function handle(RequestInterface $request, \Closure $next): ResponseInterface;
}
