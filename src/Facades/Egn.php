<?php

declare(strict_types=1);

namespace MetalinvestRemko\LaravelEgn\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool validate(string $egn)
 * @method static array<string, int>|null parse(string $egn)
 * @method static array<string, mixed>|\Illuminate\Support\Collection<string, mixed>|\stdClass|null details(string $egn, string $format = 'array', ?string $locale = null)
 * @method static string generateOne(array<string, mixed> $options = [])
 * @method static array<int, string> generate(int $count = 1, array<string, mixed> $options = [])
 */
class Egn extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'egn';
    }
}
