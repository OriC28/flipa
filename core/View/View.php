<?php

namespace Core\View;

use Core\View\Exceptions\ViewNotFoundException;

class View
{
    public function __construct(protected string $pathBase)
    {
        $this->pathBase = str_starts_with($pathBase, '/') ? $pathBase : "/$pathBase";
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
        $content = ob_get_clean();
        return $content;
    }

    public function render(string $view, array $data = []): string
    {
        $view = $this->transformPathView($view);
        $resource = __DIR__ . "/../..{$this->pathBase}/$view";
        return $this->importContentView($resource, $data);
    }
}
