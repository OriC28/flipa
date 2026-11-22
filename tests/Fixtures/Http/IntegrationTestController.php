<?php

declare(strict_types=1);

namespace Tests\Fixtures\Http;

use Tests\Fixtures\Container\DependencyClass;

class IntegrationTestController
{
    public function __construct(public DependencyClass $dependency) {}

    public function show(string $id): string
    {
        return "Item {$id} with dependency {$this->dependency->property}";
    }

    public function submit(TestFormRequest $request): string
    {
        return "Processed: " . $request->post('name');
    }
}
