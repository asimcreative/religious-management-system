<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Binds repository interfaces to their concrete implementations.
 *
 * Register each module's repository binding here as modules are built.
 * Example:
 *   $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Module repository bindings will be registered here.
        // Example:
        // $this->app->bind(
        //     \App\Contracts\Repositories\EmployeeRepositoryInterface::class,
        //     \App\Repositories\EmployeeRepository::class
        // );
    }

    public function boot(): void
    {
        //
    }
}
