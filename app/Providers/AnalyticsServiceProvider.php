<?php

namespace App\Providers;

use App\Support\Analytics\AnalyticsRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * A singleton, so each definition — and the dimension, filter and join
     * lists memoized inside it — is built once per request rather than once
     * per lookup. A report screen asks the same definition several times over
     * while rendering its selector, its filter bar and its table.
     */
    public function register(): void
    {
        $this->app->singleton(AnalyticsRegistry::class, static function (Container $app): AnalyticsRegistry {
            return new AnalyticsRegistry(
                $app,
                config('analytics.datasets', []),
            );
        });
    }

    /**
     * Resolve the {dataset} route segment to its definition.
     *
     * An unknown key throws NotFoundHttpException, so a bad or stale URL is a
     * 404 rather than a server fault, and the response does not enumerate
     * which datasets exist.
     */
    public function boot(): void
    {
        Route::bind('dataset', fn (string $key) => $this->app->make(AnalyticsRegistry::class)->get($key));
    }
}
