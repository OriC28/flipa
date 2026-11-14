<?php

namespace Tests\Fixtures\Container;

class DeepDependencyA
{
    public function __construct(public DeepDependencyB $b) {}
}
