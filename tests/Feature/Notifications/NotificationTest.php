<?php

namespace Tests\Feature\Notifications;

use App\Models\Company;
use App\Models\Notification;
use App\Services\NotificationService;
use Tests\TestCase;

/**
 * Notification — CRUD via web + API, company isolation, read state.
 */
class NotificationTest extends TestCase
{
    // ── Web: Index ─────────────────────────────────────────────────────────

    public function test_index_requires_auth(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_index_requires_view_permission(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user)->get(route('notifications.index'))->assertForbidden();
    }

    public function test_index_returns_ok_with_permission(): void
    {
        $user = $this->createUserWithCompany(['notification.view']);
        $this->actingAs($user)->get(route('notifications.index'))->assertOk();
    }

    public function test_index_only_shows_own_notifications(): void
    {
        $userA = $this->createUserWithCompany(['notification.view']);
        Notification::factory()->create(['company_id' => $userA->company_id, 'user_id' => $userA->id]);

        $userB = $this->createUserWithCompany(['notification.view']);
        Notification::factory()->create(['company_id' => $userB->company_id, 'user_id' => $userB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, Notification::where('user_id', $userA->id)->count());
    }

    // ── Mark Read ──────────────────────────────────────────────────────────

    public function test_mark_read_sets_read_at_timestamp(): void
    {
        $user = $this->createUserWithCompany(['notification.view', 'notification.read']);
        $notification = Notification::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('notifications.mark-read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_marks_all_unread_notifications(): void
    {
        $user = $this->createUserWithCompany(['notification.view', 'notification.read']);
        Notification::factory(3)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('notifications.mark-all-read'))
            ->assertRedirect();

        $unread = Notification::where('user_id', $user->id)->whereNull('read_at')->count();
        $this->assertSame(0, $unread);
    }

    public function test_mark_all_read_only_affects_own_notifications(): void
    {
        $userA = $this->createUserWithCompany(['notification.view', 'notification.read']);
        Notification::factory(2)->create([
            'company_id' => $userA->company_id,
            'user_id' => $userA->id,
            'read_at' => null,
        ]);

        $userB = $this->createUserWithCompany(['notification.view']);
        $notifB = Notification::factory()->create([
            'company_id' => $userB->company_id,
            'user_id' => $userB->id,
            'read_at' => null,
        ]);

        $this->actingAs($userA)->post(route('notifications.mark-all-read'));

        // UserB's notification should still be unread
        $this->assertNull($notifB->fresh()->read_at);
    }

    // ── Delete ─────────────────────────────────────────────────────────────

    public function test_delete_removes_own_notification(): void
    {
        $user = $this->createUserWithCompany(['notification.view', 'notification.delete']);
        $notification = Notification::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('notifications.destroy', $notification->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_delete_cannot_delete_other_users_notification(): void
    {
        $userA = $this->createUserWithCompany(['notification.view', 'notification.delete']);
        $userB = $this->createUserWithCompany(['notification.view']);
        $notifB = Notification::factory()->create([
            'company_id' => $userB->company_id,
            'user_id' => $userB->id,
        ]);

        $this->actingAs($userA)
            ->delete(route('notifications.destroy', $notifB->id))
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', ['id' => $notifB->id]);
    }

    // ── NotificationService Unit ──────────────────────────────────────────

    public function test_service_creates_notification_with_correct_data(): void
    {
        $user = $this->createUserWithCompany();
        $service = app(NotificationService::class);

        $notification = $service->notify(
            $user,
            'Test Title',
            'Test message body',
            NotificationService::TYPE_SYSTEM,
            NotificationService::PRIORITY_MEDIUM
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'title' => 'Test Title',
            'message' => 'Test message body',
            'type' => NotificationService::TYPE_SYSTEM,
            'priority' => NotificationService::PRIORITY_MEDIUM,
        ]);

        $this->assertNull($notification->read_at);
    }

    public function test_service_marks_notification_as_read(): void
    {
        $user = $this->createUserWithCompany();
        $notification = Notification::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $service = app(NotificationService::class);
        $service->markAsRead($notification->id, $user->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_service_unread_count_returns_correct_number(): void
    {
        $user = $this->createUserWithCompany();
        Notification::factory(4)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'read_at' => null,
        ]);
        Notification::factory(2)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $service = app(NotificationService::class);
        $this->actingAs($user);

        $this->assertSame(4, $service->getUnreadCount($user->id));
    }

    // ── API ───────────────────────────────────────────────────────────────

    public function test_api_notifications_list_returns_only_own(): void
    {
        $userA = $this->createUserWithCompany(['notification.view']);
        $token = $userA->createToken('test')->plainTextToken;

        Notification::factory(3)->create([
            'company_id' => $userA->company_id,
            'user_id' => $userA->id,
        ]);

        $userB = $this->createUserWithCompany(['notification.view']);
        Notification::factory(2)->create([
            'company_id' => $userB->company_id,
            'user_id' => $userB->id,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_api_mark_read_sets_read_at(): void
    {
        $user = $this->createUserWithCompany(['notification.view', 'notification.read']);
        $token = $user->createToken('test')->plainTextToken;

        $notification = Notification::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
