<?php

declare(strict_types=1);

namespace Core\View;

use Core\View\Exceptions\ViewNotFoundException;

class View
{
    public function __construct(protected array|string $pathBase) {}

    private function transformPathView(string $view): string
    {
        return str_replace('.', DIRECTORY_SEPARATOR, $view) . ".php";
    }

    private function buildResource(string $path, string $view): string
    {
        return  rtrim($path, '/\\') . DIRECTORY_SEPARATOR . ltrim($view, '/\\');
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
                $resource = $this->buildResource($path, $view);
                if (\file_exists($resource)) {
                    return $this->importContentView($resource, $data);
                }
            }
            throw new ViewNotFoundException("No view was found in $view.");
        }
        $resource = $this->buildResource($this->pathBase, $view);
        return $this->importContentView($resource, $data);
    }
}
