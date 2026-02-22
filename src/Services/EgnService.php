<?php

declare(strict_types=1);

namespace Ivajlokostov\LaravelEgn\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Ivajlokostov\LaravelEgn\Support\EgnDetailsResolver;
use Ivajlokostov\LaravelEgn\Support\EgnGenerator;
use Ivajlokostov\LaravelEgn\Support\EgnValidator;
use stdClass;

class EgnService
{
    private EgnValidator $validator;
    private EgnGenerator $generator;
    private EgnDetailsResolver $detailsResolver;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $startYear = (int) ($config['start_year'] ?? 1800);
        $endYear = (int) ($config['end_year'] ?? 2099);

        if ($startYear > $endYear) {
            throw new InvalidArgumentException('Invalid EGN year range configuration.');
        }

        $this->validator = new EgnValidator();
        $this->generator = new EgnGenerator($this->validator, $startYear, $endYear);
        $this->detailsResolver = new EgnDetailsResolver();
    }

    public function validate(string $egn): bool
    {
        return $this->validator->isValid($egn);
    }

    /**
     * Returns parsed birth date and gender if EGN is valid.
     *
     * @return array{year: int, month: int, day: int, gender: int}|null
     */
    public function parse(string $egn): ?array
    {
        return $this->validator->parse($egn);
    }

    /**
     * Returns rich EGN details as array|object|collection. Null for invalid EGN.
     *
     * @return array<string, mixed>|Collection<string, mixed>|stdClass|null
     */
    public function details(string $egn, string $format = 'array', ?string $locale = null): array|Collection|stdClass|null
    {
        $parsed = $this->validator->parse($egn);
        if ($parsed === null) {
            return null;
        }

        return $this->detailsResolver->resolve($egn, $parsed, $format, $locale);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function generateOne(array $options = []): string
    {
        return $this->generator->generateOne($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<int, string>
     */
    public function generate(int $count = 1, array $options = []): array
    {
        return $this->generator->generate($count, $options);
    }

    /**
     * Helper to normalize incoming date value from API consumers.
     */
    public static function normalizeDate(mixed $date): ?DateTimeImmutable
    {
        if ($date === null) {
            return null;
        }

        if ($date instanceof DateTimeImmutable) {
            return $date;
        }

        if ($date instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($date);
        }

        if (is_string($date)) {
            $parsed = new DateTimeImmutable($date);

            return $parsed;
        }

        throw new InvalidArgumentException('Option "date" must be DateTimeInterface|string|null.');
    }
}
