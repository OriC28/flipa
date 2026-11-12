<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Core\Database\Model;

class User extends Model
{
    protected array $fillable = ['name', 'email'];
    protected array $hidden = ['secret_token'];
}
