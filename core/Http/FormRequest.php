<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Http\Exceptions\ValidationException;
use Core\Validation\Validator;

class FormRequest extends Request
{
    public function __construct(Request $currentRequest)
    {
        parent::__construct(
            method: $currentRequest->method,
            uri: $currentRequest->uri,
            ip: $currentRequest->ip,
            host: $currentRequest->host,
            post: $currentRequest->post,
            query: $currentRequest->query,
            cookies: $currentRequest->cookies,
            headers: $currentRequest->headers,
            files: $currentRequest->files,
        );
    }
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function validate(): void
    {
        $validator = new Validator($this->all(), $this->rules());
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
    }
}
