<?php

declare(strict_types=1);

namespace MetalinvestRemko\LaravelEgn\Tests\Unit;

use MetalinvestRemko\LaravelEgn\Services\EgnService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EgnGeneratorTest extends TestCase
{
    public function test_generate_one_returns_valid_egn(): void
    {
        $service = new EgnService();
        $egn = $service->generateOne();

        self::assertSame(10, strlen($egn));
        self::assertTrue($service->validate($egn));
    }

    public function test_generate_many_respects_count(): void
    {
        $service = new EgnService();
        $egns = $service->generate(5);

        self::assertCount(5, $egns);
        foreach ($egns as $egn) {
            self::assertTrue($service->validate($egn));
        }
    }

    public function test_generate_by_full_date_and_gender_and_region(): void
    {
        $service = new EgnService();
        $egn = $service->generateOne([
            'year' => 1991,
            'month' => 12,
            'day' => 24,
            'gender' => 'male',
            'region' => 22,
        ]);

        $parsed = $service->parse($egn);

        self::assertNotNull($parsed);
        self::assertSame(1991, $parsed['year']);
        self::assertSame(12, $parsed['month']);
        self::assertSame(24, $parsed['day']);
        self::assertSame(1, $parsed['gender']);
        self::assertSame('22', substr($egn, 6, 2));
    }

    public function test_generate_with_partial_date(): void
    {
        $service = new EgnService();
        $egn = $service->generateOne([
            'month' => 2,
            'day' => 29,
        ]);

        $parsed = $service->parse($egn);
        self::assertNotNull($parsed);
        self::assertSame(2, $parsed['month']);
        self::assertSame(29, $parsed['day']);
    }

    public function test_generate_with_impossible_partial_date_in_config_range_throws(): void
    {
        $service = new EgnService([
            'start_year' => 1901,
            'end_year' => 1903,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $service->generateOne([
            'month' => 2,
            'day' => 29,
        ]);
    }
}
