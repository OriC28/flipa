<?php

declare(strict_types=1);

namespace Core\View;

use Core\View\Exceptions\ViewNotFoundException;

class View
{
    public function __construct(protected array|string $pathBase) {}

    private function getValidPath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : "/$path";
    }

    private function transformPathView(string $view): string
    {
        return str_replace('.', '/', $view) . ".php";
    }

    private function importContentView(string $resource, array $data): string
    {
        if (!file_exists($resource)) {
            throw new ViewNotFoundException("No view was found in $resource.");
        }
        extract($data);
        ob_start();
        require $resource;
        $content = ob_get_clean() ?: '';
        return $content;
    }

    public function render(string $view, array $data = []): string
    {
        $view = $this->transformPathView($view);

        if (\is_array($this->pathBase)) {
            foreach ($this->pathBase as $path) {
                $path = $this->getValidPath($path);
                $resource = "$path/$view";
                if (\file_exists($resource)) {
                    return $this->importContentView($resource, $data);
                }
            }
            throw new ViewNotFoundException("No view was found in $view.");
        }
        $resource = $this->getValidPath($this->pathBase);
        return $this->importContentView($resource, $data);
    }
}
