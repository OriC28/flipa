<?php

declare(strict_types=1);

namespace Tests\Fixtures\Container;

class CircularDependencyClassB
{
    public function __construct(circularDependencyClassA $circularDependencyClassA) {}
}
