<?php

declare(strict_types=1);

namespace Tests\Fixtures\Container;

class ClassWithDependency
{
    public function __construct(public DependencyClass $dependency) {}

    public function show(DependencyClass $dependency)
    {
        return $dependency->property;
    }
}
