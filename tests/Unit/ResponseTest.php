<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\RedirectResponse;
use Core\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_with_methods_return_new_instance_and_allow_chaining(): void
    {
        $response = new Response();
        $this->assertInstanceOf(Response::class, $response->withStatus(200));
        $this->assertInstanceOf(Response::class, $response->withHeader('Content-Type', 'application/json'));
        $this->assertInstanceOf(Response::class, $response->withBody('Welcome to system'));
        $this->assertInstanceOf(Response::class, $response->withBody(json_encode(['app_name' => 'flipa'])));
        $this->assertInstanceOf(Response::class, $response->withCookie(name: 'cookie-test', value: 'flipa'));
        $this->assertInstanceOf(Response::class, $response->withHeaders([
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization, Accept',
        ]));
    }
    public function test_with_methods_do_not_mutate_original_instance(): void
    {
        $originalResponse = (new Response())->withBody('Hello!');
        $newResponse = $originalResponse->withBody('Goodbye!');
        $this->assertNotSame($originalResponse, $newResponse);
        ob_start();
        $originalResponse->send();
        $this->assertSame('Hello!', ob_get_clean());
    }
    public function test_with_header_formats_single_header_name_to_title_case(): void
    {
        $response = (new Response())->withHeader('x-custom-header', 'custom-value');
        $this->assertArrayHasKey('X-Custom-Header', $response->getHeaders());
    }
    public function test_with_headers_formats_multiple_header_names_to_title_case(): void
    {
        $response = (new Response())->withHeaders([
            'x-custom-header' => 'custom-value',
            'content-type' => 'application/json',
            'access-control-allow-methods' => 'GET, POST, OPTIONS',
        ]);
        $titleCaseHeaders = ['X-Custom-Header', 'Content-Type', 'Access-Control-Allow-Methods'];

        foreach ($titleCaseHeaders as $key) {
            $this->assertArrayHasKey($key, $response->getHeaders());
        }
    }
    public function test_with_body_converts_arrays_to_pretty_json(): void
    {
        $response = (new Response())->withBody(['name' => 'flipa', 'type' => 'framework']);
        ob_start();
        $response->send();
        $this->assertStringContainsString("\n", ob_get_clean());
    }
    public function test_with_body_preserves_string_content(): void
    {
        $response = (new Response())->withBody('Welcome to system');
        ob_start();
        $response->send();
        $content = ob_get_clean();
        $this->assertSame('Welcome to system', $content);
        $this->assertStringNotContainsString("\n", $content);
    }
    public function test_as_json_sets_content_type_without_overwriting_existing(): void
    {
        $response = (new Response())->withHeader('Content-Type', 'text/html');
        $response->asJson();
        $this->assertSame('text/html', $response->getHeaders()['Content-Type']);
    }
    public function test_with_cookie_registers_full_cookie_configuration(): void
    {
        $response = (new Response())->withCookie(
            name: 'cookie-test',
            value: 'cookie-test-value',
            expires: time() + (86400 * 30),
            path: 'flipa.local',
            domain: 'flipa',
        );
        $this->assertSame(
            [
                'cookie-test' => [
                    'value' => 'cookie-test-value',
                    'options' => [
                        'expires' => time() + (86400 * 30),
                        'path' => 'flipa.local',
                        'domain' => 'flipa',
                        'secure' => false,
                        'httponly' => false,
                        'samesite' => 'Lax'
                    ]
                ]
            ],
            $response->getCookies()
        );
    }
    public function test_redirect_response_sets_default_302_status_and_location_header(): void
    {
        $redirectResponse = new RedirectResponse('http://flipa.local/');
        $this->assertSame(302, $redirectResponse->getStatusCode());
        $this->assertArrayHasKey('Location', $redirectResponse->getHeaders());
    }
    public function test_redirect_response_allows_custom_status_code(): void
    {
        $redirectResponse = new RedirectResponse('http://flipa.local/', 303);
        $this->assertSame(303, $redirectResponse->getStatusCode());
    }
}
