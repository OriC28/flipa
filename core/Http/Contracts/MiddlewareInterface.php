<?php

namespace Core\Http\Contracts;

interface MiddlewareInterface
{
    public function handle(RequestInterface $request, \Closure $next): ResponseInterface;
}
