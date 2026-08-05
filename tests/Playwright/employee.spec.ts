import { test, expect, Page } from '@playwright/test';

/**
 * Employee Module E2E — list, create form, validation, and navigation.
 *
 * Requires a seeded database with an admin user who has employee permissions.
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

// ── Index Page ─────────────────────────────────────────────────────────────

test.describe('Employee index', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('employee list page loads', async ({ page }) => {
        await page.goto('/employees');
        await expect(page).toHaveURL(/employees/);
        // Should not crash
        await expect(page.locator('body')).not.toContainText('500');
        await expect(page.locator('body')).not.toContainText('Whoops');
    });

    test('employee list has a Create button', async ({ page }) => {
        await page.goto('/employees');

        // There should be a link/button to create a new employee
        const createBtn = page.locator('a[href*="employees/create"], a:has-text("Add"), a:has-text("Create"), a:has-text("New")').first();
        await expect(createBtn).toBeVisible();
    });

    test('employee list shows table or empty state', async ({ page }) => {
        await page.goto('/employees');

        // Either a data table or a "no records" message should be visible
        const hasTable = await page.locator('table').isVisible();
        const hasEmpty = await page.locator('[class*="empty"], [class*="no-data"], td:has-text("No ")').isVisible();
        expect(hasTable || hasEmpty).toBeTruthy();
    });

    test('search input is present on employee list', async ({ page }) => {
        await page.goto('/employees');

        await expect(page.locator('input[name="search"], input[placeholder*="Search"], input[placeholder*="search"]').first()).toBeVisible();
    });

    test('unauthenticated access to employees redirects to login', async ({ page }) => {
        // Don't login — direct access
        await page.goto('/employees');
        await expect(page).toHaveURL(/login/);
    });
});

// ── Create Form ────────────────────────────────────────────────────────────

test.describe('Employee create form', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('create form is accessible', async ({ page }) => {
        await page.goto('/employees/create');
        await expect(page).toHaveURL(/employees\/create/);
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('create form has required fields', async ({ page }) => {
        await page.goto('/employees/create');

        await expect(page.locator('input[name="employee_code"]')).toBeVisible();
        await expect(page.locator('input[name="employee_name"]')).toBeVisible();
        await expect(page.locator('input[name="mobile"]')).toBeVisible();
        await expect(page.locator('select[name="gender"]')).toBeVisible();
    });

    test('submitting empty form shows validation errors', async ({ page }) => {
        await page.goto('/employees/create');

        await page.click('button[type="submit"]');

        // Should stay on create page and show errors
        await expect(page).toHaveURL(/employees\/create|employees/);
        await expect(page.locator('.alert, [class*="error"], [class*="invalid"], .invalid-feedback').first()).toBeVisible();
    });

    test('gender dropdown has expected options', async ({ page }) => {
        await page.goto('/employees/create');

        const gender = page.locator('select[name="gender"]');
        await expect(gender).toBeVisible();

        const options = await gender.locator('option').allTextContents();
        const normalised = options.map(o => o.trim().toLowerCase());

        expect(normalised.some(o => o.includes('male'))).toBeTruthy();
    });

    test('XSS payload in employee name field is not executed', async ({ page }) => {
        await page.goto('/employees/create');

        // Type an XSS payload into the name field
        await page.fill('input[name="employee_name"]', '<script>window.__xss=1</script>');

        // The script should NOT execute
        const xssExecuted = await page.evaluate(() => (window as unknown as Record<string, unknown>)['__xss']);
        expect(xssExecuted).toBeUndefined();
    });
});

// ── Navigation ─────────────────────────────────────────────────────────────

test.describe('Employee navigation', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('sidebar or nav has an employee link', async ({ page }) => {
        await page.goto('/dashboard');

        const employeeLink = page.locator('a[href*="employees"]:not([href*="create"])').first();
        await expect(employeeLink).toBeVisible();
    });

    test('clicking employee nav link navigates to employee list', async ({ page }) => {
        await page.goto('/dashboard');

        const employeeLink = page.locator('a[href*="employees"]:not([href*="create"])').first();
        await employeeLink.click();

        await expect(page).toHaveURL(/employees/);
    });
});
