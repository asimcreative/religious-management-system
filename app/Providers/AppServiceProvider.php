<?php

namespace App\Providers;

use App\View\Composers\NotificationComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Throw an exception in non-production environments whenever a lazy
        // load is detected, forcing eager-loading via with() / load().
        // This prevents N+1 query bugs from slipping into production.
        Model::preventLazyLoading(! app()->isProduction());

        // Prevent silently discarding fills on non-fillable attributes.
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        // Share unread notification count with the main layout via View Composer.
        View::composer('layouts.app', NotificationComposer::class);
    }
}
