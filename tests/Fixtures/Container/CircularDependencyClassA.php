<?php

declare(strict_types=1);

namespace Tests\Fixtures\Container;

class CircularDependencyClassA
{
    public function __construct(CircularDependencyClassB $circularDependencyClassB) {}
}
