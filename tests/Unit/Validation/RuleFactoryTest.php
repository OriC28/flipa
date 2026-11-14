<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Core\Validation\RuleFactory;
use Core\Validation\Rules\Email;
use Core\Validation\Rules\Max;
use Core\Validation\Rules\Required;
use PHPUnit\Framework\TestCase;

class RuleFactoryTest extends TestCase
{
    public function test_make_instantiates_correct_rule_class_from_alias(): void
    {
        $this->assertInstanceOf(Required::class, RuleFactory::make('required'));
        $this->assertInstanceOf(Email::class, RuleFactory::make('email'));
        $this->assertInstanceOf(Max::class, RuleFactory::make('max', [5]));
    }
    public function test_make_throws_exception_for_unregistered_rule(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Validation rule [nullable] is not supported.");

        RuleFactory::make('nullable');
    }
    public function test_make_casts_parameters_to_correct_types_via_reflection(): void
    {
        $instance = RuleFactory::make('min', ['5']);
        $this->assertIsInt($instance->limit);
    }
}
