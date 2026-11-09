<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Container\Container;
use Core\Database\Query\Builder;
use Core\Foundation\Application;
use Core\Log\Logger;
use Core\Session\Session;
use stdClass;
use Tests\TestBase;

class HelpersTest extends TestBase
{
    public function test_app_returns_container_instance_without_arguments(): void
    {
        $this->assertInstanceOf(Application::class, app());
    }
    public function test_session_returns_session_instance_without_arguments(): void
    {
        $this->assertInstanceOf(Session::class, session());
    }
    public function test_logger_returns_logger_instance_without_arguments(): void
    {
        $this->assertInstanceOf(Logger::class, logger());
    }
    public function test_class_basename_extracts_short_name_from_fqcn_string(): void
    {
        $fqcn = Builder::class;
        $this->assertSame('Builder', class_basename($fqcn));
    }
    public function test_class_basename_extracts_short_name_from_object_instance(): void
    {
        $object = new stdClass();
        $this->assertSame('stdClass', class_basename($object));
    }
    public function test_e_helper_escapes_html_characters_to_prevent_xss(): void
    {
        $script = '<script>alert("XSS")</script>';
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', e($script));
    }
    public function test_env_returns_value_from_superglobal(): void
    {
        $_ENV['APP_NAME'] = 'Flipa';
        $this->assertSame('Flipa', env('APP_NAME'));
    }
    public function test_env_returns_default_value_when_key_does_not_exist(): void
    {
        $defaultValue = 'Default';
        $this->assertSame($defaultValue, env('NONEXISTENT_VARIABLE', $defaultValue));
    }
    public function test_is_https_returns_true_when_server_https_is_on(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue(isHttps());
    }
    public function test_is_https_returns_false_when_server_https_is_off(): void
    {
        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse(isHttps());
    }
    public function test_is_https_returns_false_when_server_https_is_unset(): void
    {
        unset($_SERVER['HTTPS']);
        $this->assertFalse(isHttps());
    }
    public function test_csrf_field_generates_hidden_input_tag(): void
    {
        $this->assertStringContainsString('<input type="hidden"', csrf_field());
    }
}
