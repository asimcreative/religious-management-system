<?php

namespace App\Providers;

use App\Support\DataTransfer\ResourceRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DataTransferServiceProvider extends ServiceProvider
{
    /**
     * The registry is a singleton so the definition objects — and the memoized
     * column lists inside them — are built once per request rather than once
     * per lookup.
     */
    public function register(): void
    {
        $this->app->singleton(ResourceRegistry::class, static function (Container $app): ResourceRegistry {
            return new ResourceRegistry(
                $app,
                config('data-transfer.resources', []),
            );
        });
    }

    /**
     * Resolve the {resource} route segment to the module's definition.
     *
     * An unregistered key throws ResourceNotRegisteredException, which renders
     * as a 404 — a bad URL rather than a server fault, and it does not confirm
     * which module keys exist.
     */
    public function boot(): void
    {
        Route::bind('resource', fn (string $key) => $this->app->make(ResourceRegistry::class)->get($key));
    }
}
