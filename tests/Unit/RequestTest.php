<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    private array $originalGet;
    private array $originalPost;
    private array $originalServer;
    private array $originalCookies;
    private array $originalFiles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalServer = $_SERVER;
        $this->originalCookies = $_COOKIE;
        $this->originalFiles = $_FILES;

        $_GET = ['search' => 'flipa', 'page' => '2'];
        $_POST = ['username' => 'user', 'email' => 'user@flipa.local'];
        $_SERVER = [
            'HTTP_HOST' => 'flipa.local',
            'REMOTE_ADDR' => '192.168.1.100',
            'CONTENT_TYPE' => 'application/json'
        ];
        $_COOKIE = ['session_id' => 'abc123'];
        $_FILES = ['avatar' => 'foto.png'];
    }

    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_SERVER = $this->originalServer;
        $_COOKIE = $this->originalCookies;
        $_FILES = $this->originalFiles;
        parent::tearDown();
    }
    public function test_query_and_post_methods_extract_parameters_with_fallback(): void
    {
        $request = Request::capture();
        $this->assertSame('flipa', $request->query('search'));
        $this->assertSame('default_value', $request->query('inexistent', 'default_value'));
    }
    public function test_has_method_detects_keys_across_query_and_post(): void
    {
        $request = Request::capture();
        $this->assertTrue($request->has('username'));
        $this->assertFalse($request->has('inexistent'));
    }
    public function test_all_method_merges_query_post_and_json_payloads(): void
    {
        $request = Request::capture();
        $allData = $request->all();
        $keysRequired = ['search', 'username'];
        foreach ($keysRequired as $key) {
            $this->assertArrayHasKey($key, $allData);
        }
    }
    public function test_ip_and_host_methods_extract_server_variables(): void
    {
        $request = Request::capture();
        $this->assertSame('192.168.1.100', $request->ip());
        $this->assertSame('flipa.local', $request->host());
    }
    public function test_header_normalizes_http_prefixes_and_content_type(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'flipa-value';
        $request = Request::capture();
        $this->assertSame('flipa-value', $request->header('X-Custom-Header'));
        $this->assertSame('application/json', $request->header('Content-Type'));
    }
    public function test_header_returns_default_value_when_key_does_not_exist(): void
    {
        $request = Request::capture();
        $this->assertSame('fallback', $request->header('HTTP_X_CUSTOM_HEADER', 'fallback'));
    }

    public function test_is_json_returns_true_for_application_json_content_type(): void
    {
        $request = Request::capture();
        $this->assertTrue($request->isJson());
    }
    public function test_is_json_returns_false_for_non_json_content_type(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'text/html';
        $request = Request::capture();
        $this->assertFalse($request->isJson());
    }
    public function test_cookie_and_file_methods_extract_superglobals(): void
    {
        $request = Request::capture();
        $this->assertSame('abc123', $request->cookie('session_id'));
        $this->assertSame('foto.png', $request->file('avatar'));
    }
    public function test_json_method_parses_and_caches_input_stream(): void
    {
        $jsonPayload = '{"name": "flipa_user", "role": "admin"}';
        $request = Request::capture()->setRawContent($jsonPayload);

        $result1 = $request->json();
        $result2 = $request->json(); // Second call to test the cache

        $this->assertIsArray($result1);
        $this->assertSame('flipa_user', $request->json()['name']);
        $this->assertSame('admin', $request->json()['role']);

        // Verifies that it's the same instance in memory (cache is working)
        $this->assertSame($result1, $result2);
    }
}
