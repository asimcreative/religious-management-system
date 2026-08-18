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
            ->assertSee('lang="ur"', false)
            ->assertSee('dir="rtl"', false);
    }

    /**
     * The `lang` attribute alone does not mirror the layout — the sidebar,
     * tables and forms only flip once `dir="rtl"` is on `<html>`, which is
     * what every logical CSS property (inset-inline-start, margin-inline-start,
     * …) already in the stylesheets keys off. Urdu rendered with `lang="ur"`
     * but no `dir` attribute is the exact bug this guards: the page reads
     * Urdu text but keeps a left-to-right layout.
     */
    public function test_urdu_renders_right_to_left(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);
        $user->update(['language' => 'ur']);

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('dir="rtl"', false);
    }

    public function test_english_renders_left_to_right(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);
        $user->update(['language' => 'en']);

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('dir="ltr"', false);
    }

    /**
     * A person's name and email are identity strings, not translated content
     * — CSS text-overflow: ellipsis clips from the *line-end* of whatever
     * direction the containing block has, not from the end of the text's own
     * script, so under dir="rtl" the account menu was silently dropping the
     * front of the name ("Demo Admin" showed as "Admin") and email
     * ("admin@demo.test" showed as "@demo.test") with no visible ellipsis —
     * it read as data loss, not truncation. Pinning dir="ltr" on the name and
     * email keeps them a stable, correctly-ordered unit regardless of the
     * page's own direction, same as every other RTL-aware product does for
     * usernames/emails.
     */
    public function test_account_name_and_email_stay_left_to_right_even_in_urdu(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);
        $user->update(['language' => 'ur', 'name' => 'Demo Admin', 'email' => 'admin@demo.test']);

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $response->assertSeeInOrder(['dir="ltr">Demo Admin', 'dir="ltr">admin@demo.test'], false);
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
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false);

        // A guest arriving with a locale=ur cookie sees the Urdu login page.
        $this->withoutMiddleware(EncryptCookies::class)
            ->withUnencryptedCookie('locale', 'ur')
            ->get(route('login'))
            ->assertOk()
            ->assertSee('lang="ur"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('ui.locale_ur', [], 'ur'), false);
    }
}
