<?php

declare(strict_types=1);

namespace Ivajlokostov\LaravelEgn\Tests\Unit;

use Ivajlokostov\LaravelEgn\Support\EgnValidator;
use PHPUnit\Framework\TestCase;

class EgnValidatorTest extends TestCase
{
    public function test_it_validates_known_valid_egn(): void
    {
        $validator = new EgnValidator();

        self::assertTrue($validator->isValid('6101057509'));
    }

    public function test_it_rejects_invalid_checksum(): void
    {
        $validator = new EgnValidator();

        self::assertFalse($validator->isValid('6101057508'));
    }

    public function test_it_rejects_invalid_date(): void
    {
        $validator = new EgnValidator();

        self::assertFalse($validator->isValid('6102327503'));
    }

    public function test_it_parses_valid_egn(): void
    {
        $validator = new EgnValidator();
        $parsed = $validator->parse('6101057509');

        self::assertNotNull($parsed);
        self::assertSame(1961, $parsed['year']);
        self::assertSame(1, $parsed['month']);
        self::assertSame(5, $parsed['day']);
        self::assertSame(0, $parsed['gender']); // female
    }

    public function test_sample_egn_8702260780_is_valid(): void
    {
        $validator = new EgnValidator();

        self::assertTrue($validator->isValid('8702260780'));
    }
}
