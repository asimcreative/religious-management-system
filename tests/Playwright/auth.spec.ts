import { test, expect } from '@playwright/test';

/**
 * Authentication E2E — login form, validation, logout, change password.
 *
 * These tests require a seeded database with at least one active user.
 * Credentials are read from environment variables:
 *   E2E_EMAIL    (default: admin@example.com)
 *   E2E_PASSWORD (default: password)
 */

const email = process.env.E2E_EMAIL ?? 'admin@example.com';
const password = process.env.E2E_PASSWORD ?? 'password';

// ── Login Page ─────────────────────────────────────────────────────────────

test.describe('Login page', () => {
    test('shows the login form', async ({ page }) => {
        await page.goto('/login');

        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();

        // Scoped to the credential form: the page also carries the language
        // switcher, which posts its own form and so has its own submit button.
        await expect(page.locator('form[action$="/login"] button[type="submit"]')).toBeVisible();
    });

    test('page title is present', async ({ page }) => {
        await page.goto('/login');

        // The page should have a meaningful title
        await expect(page).toHaveTitle(/.+/);
    });

    test('displays error on wrong password', async ({ page }) => {
        await page.goto('/login');

        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', 'wrong-password');
        await page.click('button[type="submit"]');

        // Should stay on login page and show an error
        await expect(page).toHaveURL(/login/);
        await expect(page.locator('.alert, [class*="error"], [class*="invalid"]').first()).toBeVisible();
    });

    test('displays error on missing email', async ({ page }) => {
        await page.goto('/login');

        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');

        // Browser native validation or server-side error
        await expect(page).toHaveURL(/login/);
    });

    test('redirects authenticated users away from login', async ({ page }) => {
        // Login first
        await page.goto('/login');
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard/);

        // Going back to /login should redirect
        await page.goto('/login');
        await expect(page).toHaveURL(/dashboard/);
    });
});

// ── Successful Login ────────────────────────────────────────────────────────

test.describe('Successful login', () => {
    test('valid credentials redirect to dashboard', async ({ page }) => {
        await page.goto('/login');

        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');

        await expect(page).toHaveURL(/dashboard/);
    });

    test('dashboard renders after login', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard/);

        // Page should not be empty / have main content area.
        // `.first()` is required: this selector matches both <body> and <main>,
        // which trips Playwright strict mode.
        await expect(page.locator('main, #main, .main-content, body').first()).toBeVisible();
    });
});

// ── Logout ─────────────────────────────────────────────────────────────────

test.describe('Logout', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard/);
    });

    test('logout redirects to login page', async ({ page }) => {
        // Find and click logout — it may be a form submit button or a link
        const logoutForm = page.locator('form[action*="logout"]');
        if (await logoutForm.isVisible()) {
            await logoutForm.locator('button[type="submit"]').click();
        } else {
            // May be a link — navigate directly (GET is 405, must POST)
            await page.evaluate(() => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/logout';
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = csrf;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            });
        }

        await expect(page).toHaveURL(/login/);
    });

    test('accessing protected route after logout redirects to login', async ({ page }) => {
        // Logout
        await page.evaluate(() => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_token';
            input.value = csrf;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        });
        await page.waitForURL(/login/);

        // Now try to access dashboard directly
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/login/);
    });
});

// ── Change Password Form ────────────────────────────────────────────────────

test.describe('Change password form', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard/);
    });

    test('change password page is accessible', async ({ page }) => {
        await page.goto('/change-password');
        await expect(page).toHaveURL(/change-password/);
        await expect(page.locator('input[name="current_password"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
    });

    test('shows error on wrong current password', async ({ page }) => {
        await page.goto('/change-password');

        await page.fill('input[name="current_password"]', 'wrong-current-password');
        await page.fill('input[name="password"]', 'NewP@ssw0rd!99');
        await page.fill('input[name="password_confirmation"]', 'NewP@ssw0rd!99');
        // Scope to the page form: the app shell's topbar contains its own
        // submit button (logout), which appears first in the DOM and is hidden
        // inside a dropdown.
        await page.locator('form button[type="submit"]:visible').first().click();

        await expect(page).toHaveURL(/change-password/);
        await expect(page.locator('.alert, [class*="error"], [class*="invalid"]').first()).toBeVisible();
    });
});

// ── Forgot Password Form ────────────────────────────────────────────────────

test.describe('Forgot password', () => {
    test('forgot password page is accessible to guests', async ({ page }) => {
        await page.goto('/forgot-password');

        await expect(page.locator('input[name="email"]')).toBeVisible();

        // Scoped to the reset-request form — the language switcher on this
        // page submits a form of its own.
        await expect(page.locator('form[action$="/forgot-password"] button[type="submit"]')).toBeVisible();
    });

    test('submitting unknown email does not show 500', async ({ page }) => {
        await page.goto('/forgot-password');
        await page.fill('input[name="email"]', 'nobody@does-not-exist.test');
        await page.click('button[type="submit"]');

        // Should not crash
        await expect(page.locator('body')).not.toContainText('500');
        await expect(page.locator('body')).not.toContainText('Whoops');
    });
});
