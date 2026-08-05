<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Company;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Gate;
use LogicException;
use Tests\TestCase;

class OperationalSecurityTest extends TestCase
{
    public function test_horizon_rejects_an_inactive_user_even_when_they_are_a_system_admin(): void
    {
        $user = $this->createSuperAdmin();
        $user->update(['status' => Status::Inactive]);

        $this->assertFalse(Gate::forUser($user->fresh())->allows('viewHorizon'));
    }

    public function test_horizon_rejects_a_user_from_an_inactive_company(): void
    {
        $user = $this->createSuperAdmin();
        Company::findOrFail($user->company_id)->update(['status' => Status::Inactive]);

        $this->assertFalse(Gate::forUser($user->fresh())->allows('viewHorizon'));
    }

    public function test_production_user_seeding_requires_configured_admin_credentials(): void
    {
        $environment = app()->environment();
        $credentials = config('seed.initial_super_admin');
        app()->instance('env', 'production');
        config([
            'seed.initial_super_admin.email' => null,
            'seed.initial_super_admin.password' => null,
        ]);

        try {
            app(UserSeeder::class)->run();
            $this->fail('Production user seeding should require explicit credentials.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('INITIAL_SUPER_ADMIN_EMAIL', $exception->getMessage());
        } finally {
            app()->instance('env', $environment);
            config(['seed.initial_super_admin' => $credentials]);
        }
    }
}
