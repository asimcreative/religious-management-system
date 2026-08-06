<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Display-language switching — guests and signed-in users.
 */
class LocaleSwitchTest extends TestCase
{
    public function test_guest_can_switch_language_and_choice_is_remembered(): void
    {
        $response = $this->from(route('login'))->post(route('locale.update'), ['locale' => 'ur']);

        $response->assertRedirect(route('login'));
        $response->assertCookie('locale', 'ur');
    }

    public function test_signed_in_user_choice_is_persisted_to_their_account(): void
    {
        $user = User::factory()->create(['language' => 'en']);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('locale.update'), ['locale' => 'ur'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('ur', $user->fresh()->language);
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $user = User::factory()->create(['language' => 'en']);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->assertSame('en', $user->fresh()->language);
    }

    public function test_missing_locale_is_rejected(): void
    {
        $this->from(route('login'))
            ->post(route('locale.update'), [])
            ->assertSessionHasErrors('locale');
    }

    public function test_persisted_preference_drives_the_rendered_language(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);
        $user->update(['language' => 'ur']);

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee(__('employees.employees', [], 'ur'), false)
            ->assertSee('lang="ur"', false);
    }

    public function test_switcher_is_offered_before_sign_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('locale.update'), false)
            ->assertSee('locale-inline', false);
    }
}
