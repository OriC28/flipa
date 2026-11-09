<?php

declare(strict_types=1);

namespace Tests\Fixtures\Container;

class ClassWithUnionTypes
{
    public function __construct(public ConcreteClass | AnotherConcreteClass $unionTypeDependency) {}
}
