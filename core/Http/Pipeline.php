<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Container\Contracts\ContainerInterface;
use Core\Http\Contracts\MiddlewareInterface;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Contracts\ResponseInterface;
use Core\Http\Exceptions\MiddlewareException;

class Pipeline
{
    private array $middlewares = [];
    private RequestInterface $request;

    public function __construct(private ContainerInterface $app) {}

    public function send(RequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function through(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
                throw new MiddlewareException("Middleware $middleware does not implement interface MiddlewareInterface", 422);
            }
        }
        $this->middlewares = $middlewares;
        return $this;
    }

    public function then(\Closure $nextCore): ResponseInterface
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function (\Closure $nextCore, mixed $middleware) {
                return function (RequestInterface $request) use ($nextCore, $middleware) {
                    $middlewareInstance = \is_string($middleware)
                        ? $this->app->make($middleware)
                        : $middleware;
                    return $middlewareInstance->handle($request, $nextCore);
                };
            },
            $nextCore
        );

        return $pipeline($this->request);
    }
}
