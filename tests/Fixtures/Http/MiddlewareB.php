<?php

declare(strict_types=1);

namespace Tests\Fixtures\Http;

use Closure;
use Core\Http\Contracts\MiddlewareInterface;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Contracts\ResponseInterface;
use Override;

class MiddlewareB implements MiddlewareInterface
{
    public function __construct(protected ExecutionLog $log) {}

    #[Override]
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        $this->log->entries[] = 'MiddlewareB';
        return $next($request);
    }
}
