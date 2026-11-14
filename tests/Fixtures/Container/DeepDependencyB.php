<?php

namespace Tests\Fixtures\Container;

class DeepDependencyB
{
    public function __construct(public DeepDependencyC $c) {}
}
