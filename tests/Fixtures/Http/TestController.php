<?php

declare(strict_types=1);

namespace Tests\Fixtures\Http;

class TestController
{
    public function index(string $id)
    {
        return "Accessing a controller method with id $id.";
    }
}
