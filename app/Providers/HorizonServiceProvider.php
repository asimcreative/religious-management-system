<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if ($user === null) {
                return false;
            }

            // Allow Super Admins and any email addresses listed in HORIZON_ALLOWED_EMAILS
            if ($user->hasRole('Super Admin')) {
                return true;
            }

            $allowedEmails = array_filter(
                explode(',', (string) config('horizon.allowed_emails', ''))
            );

            return in_array($user->email, $allowedEmails, true);
        });
    }
}
