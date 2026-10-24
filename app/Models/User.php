<?php

namespace App\Models;

use Core\Database\Model;

class User extends Model
{
    protected array $fillable = ['name', 'email'];
}
