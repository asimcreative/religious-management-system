<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * Ensures every translation key in every lang file resolves correctly
 * for both supported locales (en, ur) and that the two locales stay in sync.
 *
 * Root-cause this test guards against:
 *   An empty resources/lang/ directory caused Laravel 12 to shadow the
 *   real lang/ directory at the project root, returning raw keys like
 *   "auth.login" instead of "Login" everywhere in the UI.
 */
class TranslationConsistencyTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // Lang-path sanity
    // ──────────────────────────────────────────────────────────────────────

    public function test_lang_path_points_to_root_lang_directory(): void
    {
        $langPath = app()->langPath();

        $this->assertStringNotContainsString(
            'resources' . DIRECTORY_SEPARATOR . 'lang',
            $langPath,
            'Laravel is resolving translations from resources/lang/ — this shadows the real lang/ directory. '
            . 'Delete resources/lang/ so Laravel falls back to the root lang/ directory.'
        );

        $this->assertDirectoryExists($langPath, 'lang_path() must point to an existing directory.');
        $this->assertDirectoryExists($langPath . '/en', 'lang/en/ must exist.');
        $this->assertDirectoryExists($langPath . '/ur', 'lang/ur/ must exist.');
    }

    public function test_resources_lang_directory_does_not_exist(): void
    {
        $this->assertDirectoryDoesNotExist(
            resource_path('lang'),
            'An empty resources/lang/ directory was found. Remove it — its presence causes Laravel 12 to '
            . 'ignore the real translation files in the root lang/ directory.'
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // English locale — critical keys resolve to real strings
    // ──────────────────────────────────────────────────────────────────────

    public function test_english_auth_keys_resolve(): void
    {
        App::setLocale('en');

        $keys = [
            'auth.app_tagline'   => 'Religious Affairs Management System',
            'auth.login'         => 'Login',
            'auth.logout'        => 'Logout',
            'auth.email'         => 'Email Address',
            'auth.password'      => 'Password',
            'auth.remember_me'   => 'Remember Me',
            'auth.forgot_password' => 'Forgot Password?',
            'auth.failed'        => 'These credentials do not match our records.',
            'auth.change_password' => 'Change Password',
            'auth.reset_password'  => 'Reset Password',
        ];

        foreach ($keys as $key => $expected) {
            $this->assertSame(
                $expected,
                __($key),
                "Translation key [{$key}] did not resolve for locale 'en'."
            );
        }
    }

    public function test_english_keys_do_not_return_raw_key_strings(): void
    {
        App::setLocale('en');

        $langPath = app()->langPath() . '/en';

        foreach (glob($langPath . '/*.php') as $file) {
            $namespace = basename($file, '.php');
            $translations = require $file;

            foreach (array_keys($translations) as $key) {
                $fullKey = "{$namespace}.{$key}";
                $resolved = __($fullKey);

                $this->assertNotSame(
                    $fullKey,
                    $resolved,
                    "Translation key [{$fullKey}] returned its own key — translation is not resolving for locale 'en'."
                );
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Urdu locale — critical keys resolve to real strings
    // ──────────────────────────────────────────────────────────────────────

    public function test_urdu_auth_keys_resolve(): void
    {
        App::setLocale('ur');

        $keys = [
            'auth.app_tagline'     => 'مذہبی امور کا انتظامی نظام',
            'auth.login'           => 'لاگ ان',
            'auth.email'           => 'ای میل',
            'auth.password'        => 'پاسورڈ',
            'auth.remember_me'     => 'مجھے یاد رکھیں',
            'auth.forgot_password' => 'پاسورڈ بھول گئے؟',
        ];

        foreach ($keys as $key => $expected) {
            $this->assertSame(
                $expected,
                __($key),
                "Translation key [{$key}] did not resolve for locale 'ur'."
            );
        }
    }

    public function test_urdu_keys_do_not_return_raw_key_strings(): void
    {
        App::setLocale('ur');

        $langPath = app()->langPath() . '/ur';

        foreach (glob($langPath . '/*.php') as $file) {
            $namespace = basename($file, '.php');
            $translations = require $file;

            foreach (array_keys($translations) as $key) {
                $fullKey = "{$namespace}.{$key}";
                $resolved = __($fullKey);

                $this->assertNotSame(
                    $fullKey,
                    $resolved,
                    "Translation key [{$fullKey}] returned its own key — translation is not resolving for locale 'ur'."
                );
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Key parity — en and ur must have identical keys in every file
    // ──────────────────────────────────────────────────────────────────────

    public function test_en_and_ur_have_identical_keys_for_all_files(): void
    {
        $langPath = app()->langPath();
        $enDir    = $langPath . '/en';
        $urDir    = $langPath . '/ur';

        $enFiles = array_map('basename', glob($enDir . '/*.php'));
        $urFiles = array_map('basename', glob($urDir . '/*.php'));

        sort($enFiles);
        sort($urFiles);

        $this->assertSame(
            $enFiles,
            $urFiles,
            'lang/en/ and lang/ur/ must contain the same set of files.'
        );

        foreach ($enFiles as $filename) {
            $enKeys = array_keys(require $enDir . '/' . $filename);
            $urKeys = array_keys(require $urDir . '/' . $filename);

            $missing = array_diff($enKeys, $urKeys);
            $this->assertEmpty(
                $missing,
                sprintf(
                    'lang/ur/%s is missing keys that exist in lang/en/%s: %s',
                    $filename,
                    $filename,
                    implode(', ', $missing)
                )
            );

            $extra = array_diff($urKeys, $enKeys);
            $this->assertEmpty(
                $extra,
                sprintf(
                    'lang/ur/%s has extra keys not present in lang/en/%s: %s',
                    $filename,
                    $filename,
                    implode(', ', $extra)
                )
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Locale switching
    // ──────────────────────────────────────────────────────────────────────

    public function test_locale_can_be_switched_between_en_and_ur(): void
    {
        App::setLocale('en');
        $this->assertSame('Login', __('auth.login'));

        App::setLocale('ur');
        $this->assertSame('لاگ ان', __('auth.login'));

        // Switching back to en works
        App::setLocale('en');
        $this->assertSame('Login', __('auth.login'));
    }

    public function test_default_locale_is_english(): void
    {
        $this->assertSame('en', config('app.locale'));
    }

    public function test_fallback_locale_is_english(): void
    {
        $this->assertSame('en', config('app.fallback_locale'));
    }
}
