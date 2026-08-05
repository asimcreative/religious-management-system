<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    public function test_company_notifications_are_created_for_active_users_only(): void
    {
        $company = Company::factory()->create();
        $activeUsers = User::factory()->count(2)->create(['company_id' => $company->id]);
        $inactiveUser = User::factory()->inactive()->create(['company_id' => $company->id]);

        $created = app(NotificationService::class)->notifyCompany(
            $company->id,
            'Maintenance',
            'Scheduled maintenance notice.'
        );

        $this->assertSame(2, $created);
        $this->assertDatabaseCount('notifications', 2);

        foreach ($activeUsers as $user) {
            $this->assertDatabaseHas('notifications', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'title' => 'Maintenance',
            ]);
        }

        $this->assertDatabaseMissing('notifications', [
            'company_id' => $company->id,
            'user_id' => $inactiveUser->id,
        ]);
        $this->assertSame(2, Notification::withoutGlobalScopes()->count());
    }
}
