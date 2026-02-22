<?php

declare(strict_types=1);

namespace Ivajlokostov\LaravelEgn\Tests\Feature;

use Ivajlokostov\LaravelEgn\Tests\TestCase;

class LocaleAutoDetectionTest extends TestCase
{
    public function test_details_uses_app_locale_en_when_locale_is_not_passed(): void
    {
        app()->setLocale('en');
        $details = app('egn')->details('8702260780');

        self::assertIsArray($details);
        self::assertSame('en', $details['locale']);
        self::assertSame('female', $details['gender']);
        self::assertSame('Burgas', $details['region']['name']);
    }

    public function test_details_falls_back_to_bg_for_unsupported_app_locale(): void
    {
        app()->setLocale('fr');
        $details = app('egn')->details('8702260780');

        self::assertIsArray($details);
        self::assertSame('bg', $details['locale']);
        self::assertSame('жена', $details['gender']);
        self::assertSame('Бургас', $details['region']['name']);
    }
}
