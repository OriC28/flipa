<?php

namespace Core\Http;

use Core\Container\Container;
use Core\Http\Contracts\RequestInterface;
use Core\Http\Contracts\ResponseInterface;

class Pipeline
{
    private array $middlewares = [];
    private RequestInterface $request;

    public function __construct(private Container $app) {}

    public function send(RequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function through(array $middlewares): self
    {
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
