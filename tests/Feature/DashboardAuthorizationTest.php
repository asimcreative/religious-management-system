<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    public function test_web_dashboard_requires_a_relevant_view_permission(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();

        $authorizedUser = $this->createUserWithCompany(['report.dashboard']);

        $this->actingAs($authorizedUser)
            ->get(route('dashboard'))
            ->assertOk();

        $moduleViewer = $this->createUserWithCompany(['quran.progress.view']);

        $this->actingAs($moduleViewer)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_api_dashboard_requires_the_dashboard_permission(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertForbidden();

        $authorizedUser = $this->createUserWithCompany(['report.dashboard']);

        $this->actingAs($authorizedUser, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk();
    }
}
