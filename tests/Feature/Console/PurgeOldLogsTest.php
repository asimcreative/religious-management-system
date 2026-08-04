<?php

namespace Tests\Feature\Console;

use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for the logs:purge Artisan command.
 *
 * Retention policy:
 *  - activity_log  : 730 days (2 years)
 *  - notifications : 180 days
 */
class PurgeOldLogsTest extends TestCase
{
    // ── Activity log ──────────────────────────────────────────────────────

    /** Old activity log records are deleted. */
    public function test_old_activity_log_records_are_deleted(): void
    {
        // Insert a record older than 730 days
        DB::table('activity_log')->insert([
            'log_name' => 'default',
            'description' => 'old log',
            'subject_type' => 'App\\Models\\Employee',
            'subject_id' => 1,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => 1,
            'event' => 'created',
            'properties' => '{}',
            'created_at' => now()->subDays(731),
            'updated_at' => now()->subDays(731),
        ]);

        $this->assertSame(1, DB::table('activity_log')->count());

        $this->artisan('logs:purge')
            ->assertSuccessful();

        $this->assertSame(0, DB::table('activity_log')->count());
    }

    /** Activity log records within retention window are kept. */
    public function test_recent_activity_log_records_are_kept(): void
    {
        DB::table('activity_log')->insert([
            'log_name' => 'default',
            'description' => 'recent log',
            'subject_type' => 'App\\Models\\Employee',
            'subject_id' => 1,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => 1,
            'event' => 'created',
            'properties' => '{}',
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);

        $this->artisan('logs:purge')->assertSuccessful();

        $this->assertSame(1, DB::table('activity_log')->count());
    }

    // ── Notifications ─────────────────────────────────────────────────────

    /** Old notifications are deleted. */
    public function test_old_notifications_are_deleted(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        Notification::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(181),
            'updated_at' => now()->subDays(181),
        ]);

        $this->assertSame(1, Notification::withoutGlobalScopes()->count());

        $this->artisan('logs:purge')->assertSuccessful();

        $this->assertSame(0, Notification::withoutGlobalScopes()->count());
    }

    /** Recent notifications are kept. */
    public function test_recent_notifications_are_kept(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        Notification::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('logs:purge')->assertSuccessful();

        $this->assertSame(1, Notification::withoutGlobalScopes()->count());
    }

    // ── Dry run ───────────────────────────────────────────────────────────

    /** Dry run counts records but does not delete them. */
    public function test_dry_run_does_not_delete_records(): void
    {
        DB::table('activity_log')->insert([
            'log_name' => 'default',
            'description' => 'old log',
            'subject_type' => 'App\\Models\\Employee',
            'subject_id' => 1,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => 1,
            'event' => 'created',
            'properties' => '{}',
            'created_at' => now()->subDays(800),
            'updated_at' => now()->subDays(800),
        ]);

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Notification::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(200),
        ]);

        $this->artisan('logs:purge --dry-run')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        // Both records must still exist
        $this->assertSame(1, DB::table('activity_log')->count());
        $this->assertSame(1, Notification::withoutGlobalScopes()->count());
    }

    // ── Custom retention ──────────────────────────────────────────────────

    /** Custom --activity-days overrides the default 730-day retention. */
    public function test_custom_activity_days_option(): void
    {
        // 400-day-old record — older than custom 365, newer than default 730
        DB::table('activity_log')->insert([
            'log_name' => 'default',
            'description' => 'moderately old log',
            'subject_type' => 'App\\Models\\Employee',
            'subject_id' => 1,
            'causer_type' => 'App\\Models\\User',
            'causer_id' => 1,
            'event' => 'updated',
            'properties' => '{}',
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ]);

        // Default run (730 days) — should NOT delete this record
        $this->artisan('logs:purge')->assertSuccessful();
        $this->assertSame(1, DB::table('activity_log')->count());

        // Custom 365-day run — should delete it
        $this->artisan('logs:purge --activity-days=365')->assertSuccessful();
        $this->assertSame(0, DB::table('activity_log')->count());
    }

    // ── No records ────────────────────────────────────────────────────────

    /** Command completes successfully when there is nothing to purge. */
    public function test_command_succeeds_with_no_records(): void
    {
        $this->artisan('logs:purge')->assertSuccessful();
    }
}
