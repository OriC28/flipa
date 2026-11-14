<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function test_validator_parses_pipe_separated_rules_and_populates_errors(): void
    {
        $validator = new Validator(['age' => '2'], ['age' => 'min:5|max:10']);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            [
                'age' => [
                    'The age must have a minimum of 5 characters.'
                ]
            ],
            $validator->errors()
        );
    }
    public function test_validator_stops_evaluating_rules_for_field_after_required_fails(): void
    {
        $validator = new Validator(
            ['username' => ''],
            ['username' => 'required|min:3|max:20']
        );

        $this->assertTrue($validator->fails());

        $errors = $validator->errors();

        $this->assertCount(1, $errors['username']);
        $this->assertStringContainsString('required', $errors['username'][0]);
    }
    public function test_validator_fails_returns_false_and_errors_is_empty_when_valid(): void
    {
        $validator = new Validator(['email' => 'test@flipa.local'], ['email' => 'email']);

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors());
    }
}
