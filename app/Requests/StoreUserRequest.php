<?php

declare(strict_types=1);

namespace App\Requests;

use Core\Http\FormRequest;

class StoreUserRequest extends FormRequest
{

    public function rules(): array
    {
        return ['name' => 'required|min:3', 'email' => 'required'];
    }
}
