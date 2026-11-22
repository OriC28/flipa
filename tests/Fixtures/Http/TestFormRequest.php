<?php

declare(strict_types=1);

namespace Tests\Fixtures\Http;

use Core\Http\FormRequest;

class TestFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email',
        ];
    }
}
