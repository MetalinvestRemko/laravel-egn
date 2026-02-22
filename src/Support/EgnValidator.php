<?php

declare(strict_types=1);

namespace MetalinvestRemko\LaravelEgn\Support;

use DateTimeImmutable;

class EgnValidator
{
    /**
     * @var array<int, int>
     */
    private const WEIGHTS = [2, 4, 8, 5, 10, 9, 7, 3, 6];

    public function isValid(string $egn): bool
    {
        return $this->parse($egn) !== null;
    }

    /**
     * @return array{year: int, month: int, day: int, gender: int}|null
     */
    public function parse(string $egn): ?array
    {
        if (!preg_match('/^\d{10}$/', $egn)) {
            return null;
        }

        $yy = (int) substr($egn, 0, 2);
        $mm = (int) substr($egn, 2, 2);
        $dd = (int) substr($egn, 4, 2);

        [$year, $month] = $this->resolveYearMonth($yy, $mm);
        if ($year === null || $month === null) {
            return null;
        }

        if (!$this->isValidDate($year, $month, $dd)) {
            return null;
        }

        $checksum = $this->calculateChecksum($egn);
        if ($checksum !== (int) $egn[9]) {
            return null;
        }

        $gender = ((int) $egn[8] % 2 === 0) ? 0 : 1; // 0=female, 1=male

        return [
            'year' => $year,
            'month' => $month,
            'day' => $dd,
            'gender' => $gender,
        ];
    }

    public function encodeMonthForCentury(int $year, int $month): int
    {
        if ($year >= 1800 && $year <= 1899) {
            return $month + 40;
        }

        if ($year >= 1900 && $year <= 1999) {
            return $month;
        }

        if ($year >= 2000 && $year <= 2099) {
            return $month + 20;
        }

        throw new \InvalidArgumentException('Year must be in range 1800..2099 for EGN.');
    }

    public function calculateChecksum(string $firstNineDigits): int
    {
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $firstNineDigits[$i]) * self::WEIGHTS[$i];
        }

        $mod = $sum % 11;

        return $mod === 10 ? 0 : $mod;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveYearMonth(int $yy, int $mm): array
    {
        if ($mm >= 1 && $mm <= 12) {
            return [1900 + $yy, $mm];
        }

        if ($mm >= 21 && $mm <= 32) {
            return [2000 + $yy, $mm - 20];
        }

        if ($mm >= 41 && $mm <= 52) {
            return [1800 + $yy, $mm - 40];
        }

        return [null, null];
    }

    private function isValidDate(int $year, int $month, int $day): bool
    {
        if (!checkdate($month, $day, $year)) {
            return false;
        }

        // checkdate already verifies, but this normalizes edge cases consistently.
        $date = DateTimeImmutable::createFromFormat('Y-n-j', sprintf('%d-%d-%d', $year, $month, $day));

        return $date !== false;
    }
}
