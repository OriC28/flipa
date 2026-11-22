<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Http\Exceptions\HttpException;
use Core\Http\Exceptions\ValidationException;
use Core\Http\Request;
use Tests\Fixtures\Http\TestFormRequest;
use Tests\Fixtures\Http\TestUnauthorizedFormRequest;
use Tests\TestBase;

class FormRequestValidationIntegrationTest extends TestBase
{
    private function createRequest(
        string $method = 'POST',
        string $uri = '/test',
        array $post = [],
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): Request {
        $server = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'    => $uri,
            'REMOTE_ADDR'    => '127.0.0.1',
            'HTTP_HOST'      => 'localhost',
        ];

        foreach ($headers as $key => $value) {
            $normalizedKey = str_replace('-', '_', strtoupper($key));
            if ($normalizedKey === 'CONTENT_TYPE') {
                $server['CONTENT_TYPE'] = $value;
            } else {
                $server["HTTP_$normalizedKey"] = $value;
            }
        }

        return new Request(
            method: $method,
            uri: $uri,
            ip: '127.0.0.1',
            host: 'localhost',
            post: $post,
            query: $query,
            cookies: $cookies,
            headers: $server,
            files: []
        );
    }

    public function test_form_request_passes_validation_with_valid_data(): void
    {
        $baseRequest = $this->createRequest(post: [
            'name' => 'Valid User',
            'email' => 'valid@flipa.local',
        ]);

        $formRequest = new TestFormRequest($baseRequest);
        $formRequest->validate();

        $this->assertSame('Valid User', $formRequest->post('name'));
    }

    public function test_form_request_throws_validation_exception_with_invalid_data(): void
    {
        $baseRequest = $this->createRequest(post: [
            'name' => 'Al',
            'email' => 'not-an-email',
        ]);

        $formRequest = new TestFormRequest($baseRequest);

        $this->expectException(ValidationException::class);
        $formRequest->validate();
    }

    public function test_form_request_validation_errors_contain_correct_field_messages(): void
    {
        $baseRequest = $this->createRequest(post: [
            'email' => 'only-email@flipa.local',
        ]);

        $formRequest = new TestFormRequest($baseRequest);

        try {
            $formRequest->validate();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $errors = $e->errors;
            $this->assertArrayHasKey('name', $errors);
            $this->assertNotEmpty($errors['name']);
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_form_request_throws_http_exception_403_when_authorize_returns_false(): void
    {
        $baseRequest = $this->createRequest(post: [
            'name' => 'Valid Name',
            'email' => 'test@flipa.local',
        ]);

        $formRequest = new TestUnauthorizedFormRequest($baseRequest);

        try {
            $formRequest->validate();
            $this->fail('Expected HttpException 403 was not thrown.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertStringContainsString('403', $e->getMessage());
        }
    }

    public function test_form_request_inherits_all_request_data_from_original_request(): void
    {
        $baseRequest = $this->createRequest(
            method: 'POST',
            uri: '/custom/path',
            post: ['field' => 'value'],
            query: ['page' => '2'],
            headers: ['X-Custom-Header' => 'SecretValue'],
            cookies: ['session_token' => 'cookie123']
        );

        $formRequest = new TestFormRequest($baseRequest);

        $this->assertSame('POST', $formRequest->getMethod());
        $this->assertSame('/custom/path', $formRequest->getUri());
        $this->assertSame('value', $formRequest->post('field'));
        $this->assertSame('2', $formRequest->query('page'));
        $this->assertSame('SecretValue', $formRequest->header('X-Custom-Header'));
        $this->assertSame('cookie123', $formRequest->cookie('session_token'));
    }
}
