<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Core\Http\Exceptions\HttpException;
use Core\Http\Exceptions\ValidationException;
use Core\Http\FormRequest;
use Core\Http\Request;
use PHPUnit\Framework\TestCase;

class FormRequestTest extends TestCase
{
    protected Request $baseRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseRequest = new Request(
            method: 'POST',
            uri: '/submit',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: ['name' => 'Foo', 'email' => 'foo@flipa.local'],
            query: [],
            cookies: [],
            headers: [],
            files: []
        );
    }
    public function test_form_request_initializes_with_current_request_data(): void
    {
        $formRequest = new FormRequest($this->baseRequest);
        $this->assertSame('Foo', $formRequest->post('name'));
    }
    public function test_validate_throws_http_exception_when_authorize_returns_false(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("HTTP Error 403 (Unauthorized).");
        $this->expectExceptionCode(403);

        $formRequest = new class($this->baseRequest) extends FormRequest {
            public function authorize(): bool
            {
                return false;
            }
        };
        $formRequest->validate();
    }
    public function test_validate_throws_validation_exception_when_rules_fail(): void
    {
        $baseRequest = new Request(
            method: 'POST',
            uri: '/submit',
            ip: '127.0.0.1',
            host: 'flipa.local',
            post: [],
            query: [],
            cookies: [],
            headers: [],
            files: []
        );

        $formRequest = new class($baseRequest) extends FormRequest {
            public function rules(): array
            {
                return ['email' => 'required'];
            }
        };

        try {
            $formRequest->validate();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame('422 Unprocessable Entity.', $e->getMessage());
            $this->assertSame(
                ['email' => ['The email field is required.']],
                $e->errors
            );
        }
    }
    public function test_validate_completes_silently_when_authorized_and_valid(): void
    {
        $formRequest = new class($this->baseRequest) extends FormRequest {
            public function rules(): array
            {
                return ['name' => 'min:2', 'email' => 'required|email'];
            }
        };

        $formRequest->validate();

        $this->expectNotToPerformAssertions();
    }
}
