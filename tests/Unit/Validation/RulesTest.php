<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Core\Validation\Rules\Alpha;
use Core\Validation\Rules\Boolean;
use Core\Validation\Rules\Email;
use Core\Validation\Rules\Integer;
use Core\Validation\Rules\Max;
use Core\Validation\Rules\Min;
use Core\Validation\Rules\Required;
use Core\Validation\Rules\Url;
use PHPUnit\Framework\TestCase;

class RulesTest extends TestCase
{
    public function test_required_fails_when_value_is_null(): void
    {
        $rule = new Required();
        $this->assertFalse($rule->passes('username', null));
        $this->assertSame('The username field is required.', $rule->message());
    }

    public function test_required_fails_when_value_is_empty_string(): void
    {
        $rule = new Required();
        $this->assertFalse($rule->passes('username', ''));
    }

    public function test_required_passes_when_value_is_present(): void
    {
        $rule = new Required();
        $this->assertTrue($rule->passes('username', 'test'));
    }
    public function test_min_passes_when_value_meets_condition(): void
    {
        $rule = new Min(limit: 6);
        $this->assertTrue($rule->passes('username', 'username'));
    }
    public function test_min_fails_when_value_does_not_meet_condition(): void
    {
        $rule = new Min(limit: 6);
        $this->assertFalse($rule->passes('username', 'user'));
        $this->assertSame('The username must have a minimum of 6 characters.', $rule->message());
    }
    public function test_min_passes_when_value_is_optional(): void
    {
        $rule = new Min(limit: 6);
        $this->assertTrue($rule->passes('username', null));
        $this->assertTrue($rule->passes('username', ''));
    }

    public function test_max_passes_when_value_meets_condition(): void
    {
        $rule = new Max(limit: 10);
        $this->assertTrue($rule->passes('username', 'username'));
    }
    public function test_max_fails_when_value_does_not_meet_condition(): void
    {
        $rule = new Max(limit: 10);
        $this->assertFalse($rule->passes('username', 'username_testing_max'));
        $this->assertSame('The username must have a maximum of 10 characters.', $rule->message());
    }
    public function test_max_passes_when_value_is_optional(): void
    {
        $rule = new Max(limit: 15);
        $this->assertTrue($rule->passes('username', null));
        $this->assertTrue($rule->passes('username', ''));
    }
    public function test_email_passes_with_valid_address(): void
    {
        $rule = new Email();
        $this->assertTrue($rule->passes('email', 'user@domain.com'));
        $this->assertTrue($rule->passes('email', 'name.lastname@sub.domain.org'));
        $this->assertTrue($rule->passes('email', 'user+tag@gmail.com'));
        $this->assertTrue($rule->passes('email', '123456@example.co.uk'));
    }
    public function test_email_fails_with_invalid_address(): void
    {
        $rule = new Email();
        $this->assertFalse($rule->passes('email', 'user@domain'));
        $this->assertFalse($rule->passes('email', 'user domain.com'));
        $this->assertFalse($rule->passes('email', '@domain.com'));
        $this->assertFalse($rule->passes('email', 'user@.com'));
        $this->assertFalse($rule->passes('email', 'user@domain..com'));
        $this->assertFalse($rule->passes('email', 'user.@email.com'));
        $this->assertSame('The email must be a valid email address.', $rule->message());
    }
    public function test_email_passes_when_email_is_optional(): void
    {
        $rule = new Email();
        $this->assertTrue($rule->passes('email', null));
        $this->assertTrue($rule->passes('email', ''));
    }
    public function test_integer_passes_with_number_integer_valid(): void
    {
        $rule = new Integer();
        $this->assertTrue($rule->passes('number', '123'));
        $this->assertTrue($rule->passes('number', 123));
        $this->assertTrue($rule->passes('number', '-5'));
    }

    public function test_integer_fails_with_number_integer_invalid(): void
    {
        $rule = new Integer();
        $this->assertFalse($rule->passes('number', '12.5'));
        $this->assertFalse($rule->passes('number', 'abc'));
        $this->assertFalse($rule->passes('number', '12a'));
        $this->assertSame('The number field must be an integer.', $rule->message());
    }
    public function test_integer_passes_when_value_is_optional(): void
    {
        $rule = new Integer();
        $this->assertTrue($rule->passes('number', null));
        $this->assertTrue($rule->passes('number', ''));
    }
    public function test_url_passes_with_valid_standard_url(): void
    {
        $rule = new Url();
        $urlFormatAccepted = [
            'https://google.com',
            'http://example.com',
            'ftp://ftp.archlinux.org/iso/',
            'http://example.com/path?query=1',
            'http://localhost:8080/',
            'http://192.168.1.1',
            'http://[2001:db8:85a3:8d3:1319:8a2e:370:7348]'
        ];

        foreach ($urlFormatAccepted as $url) {
            $this->assertTrue($rule->passes('url', $url));
        }
    }
    public function test_url_fails_with_url_invalid(): void
    {
        $rule = new Url();
        $urlFormatUnacceptable = [
            '://google.com',
            'https:// google.com',
            'http://example.com of test.pdf',
            "javascript:alert('XSS')",
            'http://',
            'http://-error.com'
        ];
        foreach ($urlFormatUnacceptable as $url) {
            $this->assertFalse($rule->passes('url', $url));
        }
        $this->assertSame('The url field must be a valid URL.', $rule->message());
    }
    public function test_url_passes_when_url_is_optional(): void
    {
        $rule = new Url();
        $this->assertTrue($rule->passes('url', null));
        $this->assertTrue($rule->passes('url', ''));
    }
    public function test_url_fails_with_unsupported_punycode_format(): void
    {
        $rule = new Url();
        $this->assertFalse($rule->passes('url', 'https://mañana.com'));
        $this->assertSame('The url field must be a valid URL.', $rule->message());
    }
    public function test_alpha_passes_with_string_valid(): void
    {
        $rule = new Alpha();
        $this->assertTrue($rule->passes('text', 'Test'));
        $this->assertTrue($rule->passes('text', 'abc'));
    }
    public function test_alpha_fails_with_string_invalid(): void
    {
        $rule = new Alpha();
        $this->assertFalse($rule->passes('text', 'Test_'));
        $this->assertFalse($rule->passes('text', 'Test123'));
        $this->assertFalse($rule->passes('text', '123'));
    }

    public function test_alpha_passes_when_string_is_optional(): void
    {
        $rule = new Alpha();
        $this->assertTrue($rule->passes('text', null));
        $this->assertTrue($rule->passes('text', ''));
    }
    public function test_boolean_passes_with_accepted_values(): void
    {
        $rule = new Boolean();
        $validBooleans = [true, false, 1, 0, '1', '0', 'true', 'false'];
        foreach ($validBooleans as $value) {
            $this->assertTrue($rule->passes('bool', $value));
        }
    }
    public function test_boolean_fails_with_rejected_values(): void
    {
        $rule = new Boolean();
        $invalidBooleans = ['yes', 'no', -1, '2', 'string'];
        foreach ($invalidBooleans as $value) {
            $this->assertFalse($rule->passes('bool', $value));
        }
        $this->assertSame('The bool field must be true or false.', $rule->message());
    }
}
