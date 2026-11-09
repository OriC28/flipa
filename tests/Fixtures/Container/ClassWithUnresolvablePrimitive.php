<?php

declare(strict_types=1);

namespace Tests\Fixtures\Container;

class ClassWithUnresolvablePrimitive
{
    public function __construct(public string $apiKey) {}
}
