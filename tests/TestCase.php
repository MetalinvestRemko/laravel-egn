<?php

declare(strict_types=1);

namespace Ivajlokostov\LaravelEgn\Tests;

use Ivajlokostov\LaravelEgn\EgnServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            EgnServiceProvider::class,
        ];
    }
}
