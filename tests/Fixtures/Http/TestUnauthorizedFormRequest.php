<?php

declare(strict_types=1);

namespace Tests\Fixtures\Http;

use Core\Http\FormRequest;

class TestUnauthorizedFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }
}
