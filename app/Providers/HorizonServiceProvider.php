<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\User;
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
            if (! $user instanceof User || ! $user->isActive()) {
                return false;
            }

            $company = $user->company;

            if (! $company instanceof Company || ! $company->isActive()) {
                return false;
            }

            // Allow the dedicated system administrator and explicitly allowlisted emails.
            if ($user->isSystemAdministrator()) {
                return true;
            }

            $allowedEmails = array_filter(
                explode(',', (string) config('horizon.allowed_emails', ''))
            );

            return in_array($user->email, $allowedEmails, true);
        });
    }
}
