import { test, expect, Page } from '@playwright/test';

/**
 * Application shell E2E — account menu actions and submit-button ownership.
 *
 * The shell renders above the page content, so anything it contributes comes
 * first in the DOM. Its menu actions are therefore plain buttons that post
 * forms parked at the end of <body> (see partials/shell-forms.blade.php), which
 * keeps the page's own form in charge of the first submit button.
 *
 * These tests drive the real controls a user clicks — not a synthesised form —
 * so a broken handler cannot pass unnoticed.
 *
 *   E2E_EMAIL    (default: admin@example.com)
 *   E2E_PASSWORD (default: password)
 */

const email = process.env.E2E_EMAIL ?? 'admin@example.com';
const password = process.env.E2E_PASSWORD ?? 'password';

async function login(page: Page): Promise<void> {
    await page.goto('/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
}

test.describe('Application shell', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('the page form owns the first submit button, not the shell', async ({ page }) => {
        await page.goto('/employees/create');

        const first = page.locator('button[type="submit"]').first();
        await expect(first).toBeVisible();

        // Whatever activates "the page's submit button" must reach the record
        // being edited — never Logout, which sits higher in the DOM.
        const ownerAction = await first.evaluate(
            (el) => (el as HTMLButtonElement).form?.getAttribute('action') ?? ''
        );
        expect(ownerAction).toContain('/employees');
    });

    test('the logout menu item signs the user out', async ({ page }) => {
        await page.click('.rams-userchip');
        await page.click('[data-shell-submit="#ramsLogoutForm"]');

        await expect(page).toHaveURL(/login/);

        // The session is genuinely gone, not just redirected once.
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/login/);
    });

    test('the language menu item switches the interface language', async ({ page }) => {
        await page.click('.rams-userchip');
        await page.click('[data-shell-value="ur"]');

        await expect(page.locator('html')).toHaveAttribute('lang', 'ur');

        await page.click('.rams-userchip');
        await page.click('[data-shell-value="en"]');

        await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    });
});
