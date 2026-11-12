<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Core\Database\Model;

class TestUser extends Model
{
    protected array $fillable = ['name', 'email'];
}
