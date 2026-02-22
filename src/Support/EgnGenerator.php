<?php

declare(strict_types=1);

namespace Ivajlokostov\LaravelEgn\Support;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Ivajlokostov\LaravelEgn\Services\EgnService;

class EgnGenerator
{
    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';

    public function __construct(
        private readonly EgnValidator $validator,
        private readonly int $startYear = 1800,
        private readonly int $endYear = 2099
    ) {
    }

    /**
     * @param array<string, mixed> $options
     * @return array<int, string>
     */
    public function generate(int $count = 1, array $options = []): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Count must be >= 1.');
        }

        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $this->generateOne($options);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function generateOne(array $options = []): string
    {
        $date = $this->pickDate($options);
        $gender = $this->normalizeGender($options['gender'] ?? null);
        $region = $this->normalizeRegion($options['region'] ?? null);

        $yy = (int) $date->format('y');
        $month = (int) $date->format('n');
        $day = (int) $date->format('d');
        $encodedMonth = $this->validator->encodeMonthForCentury((int) $date->format('Y'), $month);

        $prefix = sprintf('%02d%02d%02d', $yy, $encodedMonth, $day);
        $serial = $this->generateSerial($gender, $region);

        $nine = $prefix . sprintf('%03d', $serial);
        $checksum = $this->validator->calculateChecksum($nine);

        return $nine . $checksum;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function pickDate(array $options): DateTimeImmutable
    {
        $exactDate = EgnService::normalizeDate($options['date'] ?? null);
        if ($exactDate !== null) {
            $year = (int) $exactDate->format('Y');
            if ($year < $this->startYear || $year > $this->endYear) {
                throw new InvalidArgumentException(sprintf('Year must be in range %d..%d.', $this->startYear, $this->endYear));
            }

            return $exactDate;
        }

        $year = array_key_exists('year', $options) && $options['year'] !== null ? (int) $options['year'] : null;
        $month = array_key_exists('month', $options) && $options['month'] !== null ? (int) $options['month'] : null;
        $day = array_key_exists('day', $options) && $options['day'] !== null ? (int) $options['day'] : null;

        if ($year !== null && ($year < $this->startYear || $year > $this->endYear)) {
            throw new InvalidArgumentException(sprintf('Option "year" must be in range %d..%d.', $this->startYear, $this->endYear));
        }

        if ($month !== null && ($month < 1 || $month > 12)) {
            throw new InvalidArgumentException('Option "month" must be in range 1..12.');
        }

        if ($day !== null && ($day < 1 || $day > 31)) {
            throw new InvalidArgumentException('Option "day" must be in range 1..31.');
        }

        if ($year === null && $month === null && $day === null) {
            return $this->randomDate();
        }

        return $this->randomDateWithParts($year, $month, $day);
    }

    private function randomDate(): DateTimeImmutable
    {
        $start = new DateTimeImmutable(sprintf('%d-01-01', $this->startYear));
        $end = new DateTimeImmutable(sprintf('%d-12-31', $this->endYear));

        return $this->randomDateBetween($start, $end);
    }

    private function randomDateWithParts(?int $year, ?int $month, ?int $day): DateTimeImmutable
    {
        if ($day !== null && $month !== null && $year === null && !$this->existsYearForDayMonth($day, $month)) {
            throw new InvalidArgumentException('No valid date can be generated with the provided month/day in configured year range.');
        }

        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $candidateYear = $year ?? random_int($this->startYear, $this->endYear);
            $candidateMonth = $month ?? random_int(1, 12);
            $maxDay = cal_days_in_month(CAL_GREGORIAN, $candidateMonth, $candidateYear);

            if ($day !== null && $day > $maxDay) {
                if ($year !== null && $month !== null) {
                    throw new InvalidArgumentException('Invalid day for the provided year/month.');
                }

                continue;
            }

            $candidateDay = $day ?? random_int(1, $maxDay);
            $date = DateTimeImmutable::createFromFormat('Y-n-j', sprintf('%d-%d-%d', $candidateYear, $candidateMonth, $candidateDay));
            if ($date !== false) {
                return $date;
            }
        }

        throw new InvalidArgumentException('Could not build valid date from provided options.');
    }

    private function existsYearForDayMonth(int $day, int $month): bool
    {
        for ($year = $this->startYear; $year <= $this->endYear; $year++) {
            if (checkdate($month, $day, $year)) {
                return true;
            }
        }

        return false;
    }

    private function randomDateBetween(DateTimeImmutable $start, DateTimeImmutable $end): DateTimeImmutable
    {
        $diff = $start->diff($end);
        $days = (int) $diff->format('%a');
        $offset = random_int(0, max(0, $days));

        return $start->add(new DateInterval(sprintf('P%dD', $offset)));
    }

    private function normalizeGender(mixed $gender): ?string
    {
        if ($gender === null || $gender === '') {
            return null;
        }

        if (!is_string($gender)) {
            throw new InvalidArgumentException('Option "gender" must be string male|female|m|f.');
        }

        $normalized = strtolower(trim($gender));
        if (in_array($normalized, ['male', 'm'], true)) {
            return self::GENDER_MALE;
        }

        if (in_array($normalized, ['female', 'f'], true)) {
            return self::GENDER_FEMALE;
        }

        throw new InvalidArgumentException('Option "gender" must be male|female|m|f.');
    }

    private function normalizeRegion(mixed $region): ?int
    {
        if ($region === null || $region === '') {
            return null;
        }

        if (!is_int($region) && !ctype_digit((string) $region)) {
            throw new InvalidArgumentException('Option "region" must be an integer in range 0..99.');
        }

        $value = (int) $region;
        if ($value < 0 || $value > 99) {
            throw new InvalidArgumentException('Option "region" must be in range 0..99.');
        }

        return $value;
    }

    private function generateSerial(?string $gender, ?int $region): int
    {
        if ($region === null) {
            $base = random_int(0, 99);
        } else {
            $base = $region;
        }

        // region occupies first two digits in sequence RRX, where X parity defines gender.
        $lastDigit = match ($gender) {
            self::GENDER_MALE => random_int(0, 4) * 2 + 1,
            self::GENDER_FEMALE => random_int(0, 4) * 2,
            default => random_int(0, 9),
        };

        return ((int) sprintf('%02d', $base) * 10) + $lastDigit;
    }
}
