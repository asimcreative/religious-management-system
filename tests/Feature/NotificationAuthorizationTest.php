<?php

namespace Tests\Feature;

use App\Models\Notification;
use Tests\TestCase;

class NotificationAuthorizationTest extends TestCase
{
    public function test_web_notification_actions_require_their_declared_permissions(): void
    {
        $viewer = $this->createUserWithCompany(['notification.view']);
        $notification = Notification::factory()->create([
            'company_id' => $viewer->company_id,
            'user_id' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('notifications.index'))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('notifications.mark-read', $notification))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->delete(route('notifications.destroy', $notification))
            ->assertForbidden();

        $manager = $this->createUserWithCompany([
            'notification.view',
            'notification.read',
            'notification.delete',
        ]);
        $managedNotification = Notification::factory()->create([
            'company_id' => $manager->company_id,
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('notifications.mark-read', $managedNotification))
            ->assertRedirect();

        $this->actingAs($manager)
            ->delete(route('notifications.destroy', $managedNotification))
            ->assertRedirect();
    }

    public function test_api_notification_actions_require_their_declared_permissions(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/unread-notifications-count')
            ->assertForbidden();

        $manager = $this->createUserWithCompany([
            'notification.view',
            'notification.read',
            'notification.delete',
        ]);
        $notification = Notification::factory()->create([
            'company_id' => $manager->company_id,
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk();

        $this->actingAs($manager, 'sanctum')
            ->getJson('/api/v1/me/unread-notifications-count')
            ->assertOk();

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->actingAs($manager, 'sanctum')
            ->deleteJson("/api/v1/notifications/{$notification->id}")
            ->assertNoContent();
    }
}
