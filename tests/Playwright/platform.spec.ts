import { test, expect, Page } from '@playwright/test';

/**
 * Platform account E2E — the boundary, the platform dashboard, and signing in
 * to a company.
 *
 * Requires a seeded database (`php artisan migrate:fresh --seed`), which
 * creates the SYSTEM platform account and the DEMO tenant.
 *
 * Credentials come from environment variables so a non-default seed still runs:
 *   E2E_PLATFORM_EMAIL    (default: superadmin@rams.test)
 *   E2E_PLATFORM_PASSWORD (default: SuperAdmin@1234)
 */

const email = process.env.E2E_PLATFORM_EMAIL ?? 'superadmin@rams.test';
const password = process.env.E2E_PLATFORM_PASSWORD ?? 'SuperAdmin@1234';

// The Insights section also links to /reports/employees, which ends in the same
// segment — exclude it so this asks about the Employees module and nothing else.
const EMPLOYEES_LINK = 'a[href$="/employees"]:not([href*="/reports"])';

async function signIn(page: Page) {
    await page.goto('/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('form[action$="/login"] button[type="submit"]');
    await page.waitForURL(/dashboard|companies/);
}

/** Leave the company if a previous test left an impersonation running. */
async function leaveCompany(page: Page) {
    const exit = page.locator('.rams-impersonation form button[type="submit"]');

    if (await exit.count()) {
        await exit.first().click();
        await page.waitForURL(/companies/);
    }
}

test.describe('Platform account', () => {
    test.beforeEach(async ({ page }) => {
        await signIn(page);
        await leaveCompany(page);
    });

    test('the dashboard is about companies, not employees', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page.getByRole('heading', { name: /newest companies/i })).toBeVisible();
        await expect(page.locator('.stat-card').first()).toBeVisible();
    });

    test('the menu offers Companies and no tenant module', async ({ page }) => {
        await page.goto('/dashboard');

        const sidebar = page.locator('[data-sidebar]');

        await expect(sidebar.locator('a[href$="/companies"]')).toHaveCount(1);
        await expect(sidebar.locator(EMPLOYEES_LINK)).toHaveCount(0);
        await expect(sidebar.locator('a[href$="/settings"]')).toHaveCount(0);
    });

    test('a tenant module URL redirects to the company register', async ({ page }) => {
        await page.goto('/employees');

        await expect(page).toHaveURL(/\/companies/);
        await expect(page.locator('.alert, [role="alert"]').first()).toBeVisible();
    });

    test('the company list offers a way in to each company', async ({ page }) => {
        await page.goto('/companies');

        await expect(
            page.locator('form[action*="/impersonate"] button[type="submit"]').first()
        ).toBeVisible();
    });
});

test.describe('Viewing a company', () => {
    test.beforeEach(async ({ page }) => {
        await signIn(page);
        await leaveCompany(page);

        await page.goto('/companies');

        // The platform's own company has no button, so the first one belongs to
        // a tenant.
        await page.locator('form[action*="/impersonate"] button[type="submit"]').first().click();

        // ui.js replaces the inline confirm() with an accessible modal.
        const accept = page.locator('.modal.show [data-confirm-accept]');
        await accept.waitFor({ state: 'visible' });
        await accept.click();

        await page.waitForURL(/dashboard/);
    });

    test.afterEach(async ({ page }) => {
        await leaveCompany(page);
    });

    test('a banner names the company and carries the way out', async ({ page }) => {
        const banner = page.locator('.rams-impersonation');

        await expect(banner).toBeVisible();
        await expect(banner).toContainText(/viewing/i);
        await expect(banner.locator('form[action$="/impersonate/stop"] button')).toBeVisible();
    });

    test('the tenant menu is back', async ({ page }) => {
        const sidebar = page.locator('[data-sidebar]');

        await expect(sidebar.locator(EMPLOYEES_LINK)).toHaveCount(1);
        await expect(sidebar.locator('a[href$="/companies"]')).toHaveCount(0);
    });

    test('lists open but offer no way to add or edit', async ({ page }) => {
        await page.goto('/employees');

        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('a[href$="/employees/create"]')).toHaveCount(0);
        await expect(page.getByRole('button', { name: /import/i })).toHaveCount(0);
    });

    test('the banner survives navigation', async ({ page }) => {
        await page.goto('/employees');
        await expect(page.locator('.rams-impersonation')).toBeVisible();

        await page.goto('/teachers');
        await expect(page.locator('.rams-impersonation')).toBeVisible();
    });

    test('leaving returns to the platform account', async ({ page }) => {
        await page.locator('.rams-impersonation form button[type="submit"]').click();

        await expect(page).toHaveURL(/\/companies/);
        await expect(page.locator('.rams-impersonation')).toHaveCount(0);
        await expect(page.locator('[data-sidebar] a[href$="/companies"]')).toHaveCount(1);
    });
});
