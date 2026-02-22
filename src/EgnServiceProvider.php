<?php

declare(strict_types=1);

namespace Ivajlokostov\LaravelEgn;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Ivajlokostov\LaravelEgn\Services\EgnService;

class EgnServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/egn.php', 'egn');

        $this->app->singleton('egn', function ($app): EgnService {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('egn', []);

            return new EgnService($config);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/egn.php' => config_path('egn.php'),
        ], 'egn-config');

        Validator::extend('egn', function ($attribute, $value): bool {
            if (!is_string($value) && !is_numeric($value)) {
                return false;
            }

            return app('egn')->validate((string) $value);
        }, 'The :attribute must be a valid Bulgarian EGN.');
    }
}
