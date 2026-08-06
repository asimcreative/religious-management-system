<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
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

    /**
     * The real guest journey: switching language must actually change what the
     * guest sees on the very next page. Storing the cookie is not enough — the
     * guest screens must READ it. This regressed because SetLocale was applied
     * only to the authenticated route group, so a guest could switch language
     * and have the cookie stored, but the login page they were returned to
     * never read it. SetLocale now runs on the whole web group.
     *
     * EncryptCookies is disabled here only to inject the cookie in plaintext —
     * that middleware is orthogonal plumbing, already covered by
     * test_guest_can_switch_language_and_choice_is_remembered (which asserts the
     * encrypted round-trip). What this test isolates is that a guest request
     * carrying a `locale` cookie renders in that language.
     */
    public function test_guest_locale_cookie_drives_the_rendered_login_page(): void
    {
        // Baseline: default English.
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('lang="en"', false);

        // A guest arriving with a locale=ur cookie sees the Urdu login page.
        $this->withoutMiddleware(EncryptCookies::class)
            ->withUnencryptedCookie('locale', 'ur')
            ->get(route('login'))
            ->assertOk()
            ->assertSee('lang="ur"', false)
            ->assertSee(__('ui.locale_ur', [], 'ur'), false);
    }
}
