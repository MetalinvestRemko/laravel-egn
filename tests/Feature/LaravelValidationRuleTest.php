<?php

declare(strict_types=1);

namespace MetalinvestRemko\LaravelEgn\Tests\Feature;

use Illuminate\Support\Facades\Validator;
use MetalinvestRemko\LaravelEgn\Tests\TestCase;

class LaravelValidationRuleTest extends TestCase
{
    public function test_egn_validation_rule_accepts_valid_egn(): void
    {
        $validator = Validator::make(
            ['egn' => '6101057509'],
            ['egn' => ['required', 'egn']]
        );

        self::assertFalse($validator->fails());
    }

    public function test_egn_validation_rule_rejects_invalid_egn(): void
    {
        $validator = Validator::make(
            ['egn' => '6101057508'],
            ['egn' => ['required', 'egn']]
        );

        self::assertTrue($validator->fails());
    }
}
