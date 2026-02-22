<?php

declare(strict_types=1);

namespace MetalinvestRemko\LaravelEgn\Support;

use DateTimeImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use stdClass;

class EgnDetailsResolver
{
    /**
     * Region map by inclusive upper bound for EGN serial (positions 7-9).
     *
     * @var array<int, string>
     */
    private const REGION_UPPER_BOUNDS = [
        43 => ['bg' => 'Благоевград', 'en' => 'Blagoevgrad'],
        93 => ['bg' => 'Бургас', 'en' => 'Burgas'],
        139 => ['bg' => 'Варна', 'en' => 'Varna'],
        169 => ['bg' => 'Велико Търново', 'en' => 'Veliko Tarnovo'],
        183 => ['bg' => 'Видин', 'en' => 'Vidin'],
        217 => ['bg' => 'Враца', 'en' => 'Vratsa'],
        233 => ['bg' => 'Габрово', 'en' => 'Gabrovo'],
        281 => ['bg' => 'Кърджали', 'en' => 'Kardzhali'],
        301 => ['bg' => 'Кюстендил', 'en' => 'Kyustendil'],
        319 => ['bg' => 'Ловеч', 'en' => 'Lovech'],
        341 => ['bg' => 'Монтана', 'en' => 'Montana'],
        377 => ['bg' => 'Пазарджик', 'en' => 'Pazardzhik'],
        395 => ['bg' => 'Перник', 'en' => 'Pernik'],
        435 => ['bg' => 'Плевен', 'en' => 'Pleven'],
        501 => ['bg' => 'Пловдив', 'en' => 'Plovdiv'],
        527 => ['bg' => 'Разград', 'en' => 'Razgrad'],
        555 => ['bg' => 'Русе', 'en' => 'Ruse'],
        575 => ['bg' => 'Силистра', 'en' => 'Silistra'],
        601 => ['bg' => 'Сливен', 'en' => 'Sliven'],
        623 => ['bg' => 'Смолян', 'en' => 'Smolyan'],
        721 => ['bg' => 'София - град', 'en' => 'Sofia City'],
        751 => ['bg' => 'София - окръг', 'en' => 'Sofia District'],
        789 => ['bg' => 'Стара Загора', 'en' => 'Stara Zagora'],
        821 => ['bg' => 'Добрич (Толбухин)', 'en' => 'Dobrich (Tolbukhin)'],
        843 => ['bg' => 'Търговище', 'en' => 'Targovishte'],
        871 => ['bg' => 'Хасково', 'en' => 'Haskovo'],
        903 => ['bg' => 'Шумен', 'en' => 'Shumen'],
        925 => ['bg' => 'Ямбол', 'en' => 'Yambol'],
        999 => ['bg' => 'Друг/Неизвестен', 'en' => 'Other/Unknown'],
    ];

    /**
     * @var array<int, string>
     */
    private const MONTHS = [
        'bg' => [
            1 => 'януари',
            2 => 'февруари',
            3 => 'март',
            4 => 'април',
            5 => 'май',
            6 => 'юни',
            7 => 'юли',
            8 => 'август',
            9 => 'септември',
            10 => 'октомври',
            11 => 'ноември',
            12 => 'декември',
        ],
        'en' => [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ],
    ];

    /**
     * @var array<string, string>
     */
    private const WEEKDAYS = [
        'bg' => [
            'Monday' => 'понеделник',
            'Tuesday' => 'вторник',
            'Wednesday' => 'сряда',
            'Thursday' => 'четвъртък',
            'Friday' => 'петък',
            'Saturday' => 'събота',
            'Sunday' => 'неделя',
        ],
        'en' => [
            'Monday' => 'Monday',
            'Tuesday' => 'Tuesday',
            'Wednesday' => 'Wednesday',
            'Thursday' => 'Thursday',
            'Friday' => 'Friday',
            'Saturday' => 'Saturday',
            'Sunday' => 'Sunday',
        ],
    ];

    /**
     * @var array<int, array{start: string, end: string, bg_name: string, en_name: string, bg_range: string, en_range: string}>
     */
    private const ZODIAC = [
        ['start' => '03-21', 'end' => '04-19', 'bg_name' => 'Овен', 'en_name' => 'Aries', 'bg_range' => '21 март - 19 април', 'en_range' => '21 March - 19 April'],
        ['start' => '04-20', 'end' => '05-20', 'bg_name' => 'Телец', 'en_name' => 'Taurus', 'bg_range' => '20 април - 20 май', 'en_range' => '20 April - 20 May'],
        ['start' => '05-21', 'end' => '06-20', 'bg_name' => 'Близнаци', 'en_name' => 'Gemini', 'bg_range' => '21 май - 20 юни', 'en_range' => '21 May - 20 June'],
        ['start' => '06-21', 'end' => '07-22', 'bg_name' => 'Рак', 'en_name' => 'Cancer', 'bg_range' => '21 юни - 22 юли', 'en_range' => '21 June - 22 July'],
        ['start' => '07-23', 'end' => '08-22', 'bg_name' => 'Лъв', 'en_name' => 'Leo', 'bg_range' => '23 юли - 22 август', 'en_range' => '23 July - 22 August'],
        ['start' => '08-23', 'end' => '09-22', 'bg_name' => 'Дева', 'en_name' => 'Virgo', 'bg_range' => '23 август - 22 септември', 'en_range' => '23 August - 22 September'],
        ['start' => '09-23', 'end' => '10-22', 'bg_name' => 'Везни', 'en_name' => 'Libra', 'bg_range' => '23 септември - 22 октомври', 'en_range' => '23 September - 22 October'],
        ['start' => '10-23', 'end' => '11-21', 'bg_name' => 'Скорпион', 'en_name' => 'Scorpio', 'bg_range' => '23 октомври - 21 ноември', 'en_range' => '23 October - 21 November'],
        ['start' => '11-22', 'end' => '12-21', 'bg_name' => 'Стрелец', 'en_name' => 'Sagittarius', 'bg_range' => '22 ноември - 21 декември', 'en_range' => '22 November - 21 December'],
        ['start' => '12-22', 'end' => '01-19', 'bg_name' => 'Козирог', 'en_name' => 'Capricorn', 'bg_range' => '22 декември - 19 януари', 'en_range' => '22 December - 19 January'],
        ['start' => '01-20', 'end' => '02-18', 'bg_name' => 'Водолей', 'en_name' => 'Aquarius', 'bg_range' => '20 януари - 18 февруари', 'en_range' => '20 January - 18 February'],
        ['start' => '02-19', 'end' => '03-20', 'bg_name' => 'Риби', 'en_name' => 'Pisces', 'bg_range' => '19 февруари - 20 март', 'en_range' => '19 February - 20 March'],
    ];

    /**
     * @param array{year: int, month: int, day: int, gender: int} $parsed
     * @return array<string, mixed>|Collection<string, mixed>|stdClass
     */
    public function resolve(string $egn, array $parsed, string $format = 'array', ?string $locale = null): array|Collection|stdClass
    {
        $locale = $this->resolveLocale($locale);
        $birthDate = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $parsed['year'], $parsed['month'], $parsed['day']));
        $serial = (int) substr($egn, 6, 3);
        $region = $this->resolveRegion($serial, $locale);

        $genderCode = $parsed['gender'] === 1 ? 'male' : 'female';
        $gender = $this->localizeGender($genderCode, $locale);
        $birthOrder = $this->resolveBirthOrder($serial, $region['range_start'], $region['range_end']);
        $zodiac = $this->resolveZodiac($parsed['month'], $parsed['day'], $locale);
        $weekdayEn = $birthDate->format('l');
        $weekday = self::WEEKDAYS[$locale][$weekdayEn] ?? $weekdayEn;
        $isoDate = $birthDate->format('Y-m-d');
        $dateLocalized = $this->formatDate($birthDate, $locale, $weekday, $isoDate);

        $today = new DateTimeImmutable('today');
        $age = $birthDate->diff($today)->y;

        $details = [
            'egn' => $egn,
            'valid' => true,
            'locale' => $locale,
            'gender' => $gender,
            'gender_code' => $genderCode,
            'birth_date' => [
                'iso' => $isoDate,
                'year' => $parsed['year'],
                'month' => $parsed['month'],
                'day' => $parsed['day'],
                'weekday' => $weekday,
                'formatted' => $dateLocalized,
            ],
            'age' => $age,
            'region' => [
                'code' => $serial,
                'name' => $region['name'],
                'range_start' => $region['range_start'],
                'range_end' => $region['range_end'],
            ],
            'birth_order' => $birthOrder,
            'zodiac' => [
                'name' => $zodiac['name'],
                'range' => $zodiac['range'],
                'label' => sprintf('%s (%s)', $zodiac['name'], $zodiac['range']),
            ],
        ];

        return match (strtolower($format)) {
            'array' => $details,
            'collection' => new Collection($details),
            'object' => $this->toObject($details),
            default => throw new InvalidArgumentException('Format must be one of: array|collection|object.'),
        };
    }

    /**
     * @return array{name: string, range_start: int, range_end: int}
     */
    private function resolveRegion(int $serial, string $locale): array
    {
        $previousUpper = -1;

        foreach (self::REGION_UPPER_BOUNDS as $upper => $names) {
            if ($serial <= $upper) {
                return [
                    'name' => $names[$locale],
                    'range_start' => $previousUpper + 1,
                    'range_end' => $upper,
                ];
            }

            $previousUpper = $upper;
        }

        return [
            'name' => $locale === 'en' ? 'Other/Unknown' : 'Друг/Неизвестен',
            'range_start' => 0,
            'range_end' => 999,
        ];
    }

    private function resolveBirthOrder(int $serial, int $rangeStart, int $rangeEnd): int
    {
        $parity = $serial % 2;
        $firstWithParity = ($rangeStart % 2 === $parity) ? $rangeStart : $rangeStart + 1;
        $lastWithParity = ($rangeEnd % 2 === $parity) ? $rangeEnd : $rangeEnd - 1;

        if ($firstWithParity > $lastWithParity || $serial < $firstWithParity || $serial > $lastWithParity) {
            return 0;
        }

        return intdiv($serial - $firstWithParity, 2) + 1;
    }

    /**
     * @return array{name: string, range: string}
     */
    private function resolveZodiac(int $month, int $day, string $locale): array
    {
        $md = sprintf('%02d-%02d', $month, $day);

        foreach (self::ZODIAC as $zodiac) {
            $start = $zodiac['start'];
            $end = $zodiac['end'];

            if ($start <= $end) {
                if ($md >= $start && $md <= $end) {
                    return $this->localizeZodiac($zodiac, $locale);
                }

                continue;
            }

            if ($md >= $start || $md <= $end) {
                return $this->localizeZodiac($zodiac, $locale);
            }
        }

        return ['name' => $locale === 'en' ? 'Unknown' : 'Неизвестно', 'range' => ''];
    }

    /**
     * @param array<string, mixed> $value
     */
    private function toObject(array $value): stdClass
    {
        $object = new stdClass();

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $object->{$key} = $this->toObject($item);
            } else {
                $object->{$key} = $item;
            }
        }

        return $object;
    }

    private function resolveLocale(?string $locale): string
    {
        $candidate = $locale;
        if ($candidate === null && function_exists('app')) {
            try {
                $candidate = (string) app()->getLocale();
            } catch (\Throwable) {
                $candidate = null;
            }
        }

        $candidate = strtolower(substr((string) $candidate, 0, 2));

        return in_array($candidate, ['bg', 'en'], true) ? $candidate : 'bg';
    }

    private function localizeGender(string $genderCode, string $locale): string
    {
        if ($locale === 'en') {
            return $genderCode === 'male' ? 'male' : 'female';
        }

        return $genderCode === 'male' ? 'мъж' : 'жена';
    }

    private function formatDate(DateTimeImmutable $birthDate, string $locale, string $weekday, string $isoDate): string
    {
        $day = (int) $birthDate->format('j');
        $month = self::MONTHS[$locale][(int) $birthDate->format('n')];
        $year = (int) $birthDate->format('Y');

        if ($locale === 'en') {
            return sprintf('%d %s %d (%s) (%s)', $day, $month, $year, $weekday, $isoDate);
        }

        return sprintf('%d %s %d г. (%s) (%s)', $day, $month, $year, $weekday, $isoDate);
    }

    /**
     * @param array<string, string> $zodiac
     * @return array{name: string, range: string}
     */
    private function localizeZodiac(array $zodiac, string $locale): array
    {
        if ($locale === 'en') {
            return ['name' => $zodiac['en_name'], 'range' => $zodiac['en_range']];
        }

        return ['name' => $zodiac['bg_name'], 'range' => $zodiac['bg_range']];
    }
}
