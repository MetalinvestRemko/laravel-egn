<?php

declare(strict_types=1);

namespace Ivajlokostov\LaravelEgn\Tests\Unit;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Ivajlokostov\LaravelEgn\Services\EgnService;
use PHPUnit\Framework\TestCase;
use stdClass;

class EgnDetailsTest extends TestCase
{
    public function test_details_returns_expected_fields_as_array(): void
    {
        $service = new EgnService();
        $details = $service->details('8702260780', 'array');

        self::assertIsArray($details);
        self::assertSame('8702260780', $details['egn']);
        self::assertTrue($details['valid']);
        self::assertSame('bg', $details['locale']);
        self::assertSame('1987-02-26', $details['birth_date']['iso']);
        self::assertSame('четвъртък', $details['birth_date']['weekday']);
        self::assertSame('26 февруари 1987 г. (четвъртък) (1987-02-26)', $details['birth_date']['formatted']);
        self::assertSame('Бургас', $details['region']['name']);
        self::assertSame(78, $details['region']['code']);
        self::assertSame(18, $details['birth_order']);
        self::assertSame('Риби', $details['zodiac']['name']);
        self::assertSame('Риби (19 февруари - 20 март)', $details['zodiac']['label']);
    }

    public function test_details_returns_stdclass_when_object_format_is_requested(): void
    {
        $service = new EgnService();
        $details = $service->details('8702260780', 'object');

        self::assertInstanceOf(stdClass::class, $details);
        self::assertSame('8702260780', $details->egn);
        self::assertSame('Бургас', $details->region->name);
    }

    public function test_details_returns_collection_when_collection_format_is_requested(): void
    {
        $service = new EgnService();
        $details = $service->details('8702260780', 'collection');

        self::assertInstanceOf(Collection::class, $details);
        self::assertSame('8702260780', $details->get('egn'));
        self::assertSame('Бургас', $details->get('region')['name']);
    }

    public function test_details_returns_english_text_when_en_locale_is_requested(): void
    {
        $service = new EgnService();
        $details = $service->details('8702260780', 'array', 'en');

        self::assertIsArray($details);
        self::assertSame('en', $details['locale']);
        self::assertSame('female', $details['gender']);
        self::assertSame('Thursday', $details['birth_date']['weekday']);
        self::assertSame('26 February 1987 (Thursday) (1987-02-26)', $details['birth_date']['formatted']);
        self::assertSame('Burgas', $details['region']['name']);
        self::assertSame('Pisces', $details['zodiac']['name']);
        self::assertSame('Pisces (19 February - 20 March)', $details['zodiac']['label']);
    }

    public function test_details_returns_null_for_invalid_egn(): void
    {
        $service = new EgnService();

        self::assertNull($service->details('0000000000'));
    }

    public function test_details_fallbacks_to_bg_for_unsupported_locale(): void
    {
        $service = new EgnService();
        $details = $service->details('8702260780', 'array', 'de');

        self::assertIsArray($details);
        self::assertSame('bg', $details['locale']);
        self::assertSame('жена', $details['gender']);
    }

    public function test_details_throws_for_invalid_format(): void
    {
        $service = new EgnService();
        $this->expectException(InvalidArgumentException::class);

        $service->details('8702260780', 'xml');
    }
}
