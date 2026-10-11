<?php

declare(strict_types=1);

namespace Core\Http;

class Route
{
    public string $regexCompiled;
    private array $parametersNames = [];

    public function __construct(
        public string $path,
        public string $method,
        public \Closure $handler,
    ) {
        $this->method = strtoupper($method);
        $this->regexCompiled = $this->convertToRegex($this->path);
        $this->parametersNames = $this->getParametersName($this->path);
    }

    private function convertToRegex(string $path): string
    {
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path);
        return "#^$regex$#";
    }

    private function getParametersName(string $path): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $path, $matches);
        return $matches[1];
    }

    public function getHandler(): \Closure
    {
        return $this->handler;
    }

    public function match(string $uri): ?array
    {
        $result = (bool) preg_match($this->regexCompiled, $uri, $matches);
        $params_values = \array_slice($matches, 1);
        if (\count($params_values) === \count($this->parametersNames)) {
            $params = array_combine($this->parametersNames, $params_values);
        }
        return $result ? $params ?? [] : null;
    }
}
