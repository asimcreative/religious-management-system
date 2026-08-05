import { test, expect, Page } from '@playwright/test';

/**
 * Attendance E2E — Quran and Salah attendance create pages, form UI checks.
 *
 * These tests verify the UI is correctly rendered and accessible.
 * Actual submission tests require pre-seeded classes and jamaats.
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

// ── Quran Attendance ───────────────────────────────────────────────────────

test.describe('Quran attendance index', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('quran attendance list page loads', async ({ page }) => {
        await page.goto('/quran-attendance');
        await expect(page).toHaveURL(/quran-attendance/);
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('quran attendance list shows table or empty state', async ({ page }) => {
        await page.goto('/quran-attendance');

        const hasContent = await page.locator('table, [class*="empty"], td').first().isVisible();
        expect(hasContent).toBeTruthy();
    });

    test('unauthenticated access redirects to login', async ({ page }) => {
        await page.goto('/quran-attendance');
        await expect(page).toHaveURL(/login/);
    });
});

test.describe('Quran attendance create', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('create page loads without error', async ({ page }) => {
        await page.goto('/quran-attendance/create');
        await expect(page.locator('body')).not.toContainText('500');
        await expect(page.locator('body')).not.toContainText('Whoops');
    });

    test('create page has date field', async ({ page }) => {
        await page.goto('/quran-attendance/create');

        const dateField = page.locator('input[type="date"][name*="date"], input[name="attendance_date"]').first();
        await expect(dateField).toBeVisible();
    });

    test('create page has class selector', async ({ page }) => {
        await page.goto('/quran-attendance/create');

        const classSelect = page.locator('select[name="class_id"], select[name*="class"]').first();
        await expect(classSelect).toBeVisible();
    });
});

// ── Salah Attendance ───────────────────────────────────────────────────────

test.describe('Salah attendance index', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('salah attendance list page loads', async ({ page }) => {
        await page.goto('/salah-attendance');
        await expect(page).toHaveURL(/salah-attendance/);
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('unauthenticated access redirects to login', async ({ page }) => {
        await page.goto('/salah-attendance');
        await expect(page).toHaveURL(/login/);
    });
});

test.describe('Salah attendance create', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('create page loads without error', async ({ page }) => {
        await page.goto('/salah-attendance/create');
        await expect(page.locator('body')).not.toContainText('500');
        await expect(page.locator('body')).not.toContainText('Whoops');
    });

    test('create page has date field', async ({ page }) => {
        await page.goto('/salah-attendance/create');

        const dateField = page.locator('input[type="date"], input[name="attendance_date"]').first();
        await expect(dateField).toBeVisible();
    });

    test('create page has jamaat selector', async ({ page }) => {
        await page.goto('/salah-attendance/create');

        const jamaatSelect = page.locator('select[name="jamaat_id"]').first();
        await expect(jamaatSelect).toBeVisible();
    });

    test('create page has prayer selector', async ({ page }) => {
        await page.goto('/salah-attendance/create');

        const prayerSelect = page.locator('select[name="prayer_id"]').first();
        await expect(prayerSelect).toBeVisible();
    });
});

// ── Quran Progress ─────────────────────────────────────────────────────────

test.describe('Quran progress index', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('quran progress list page loads', async ({ page }) => {
        await page.goto('/quran-progress');
        await expect(page).toHaveURL(/quran-progress/);
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('quran progress create form has completion percentage field', async ({ page }) => {
        await page.goto('/quran-progress/create');

        const pctField = page.locator('input[name="completion_percentage"]').first();
        await expect(pctField).toBeVisible();
    });

    test('completion percentage field has min=0 max=100', async ({ page }) => {
        await page.goto('/quran-progress/create');

        const pctField = page.locator('input[name="completion_percentage"]').first();
        const min = await pctField.getAttribute('min');
        const max = await pctField.getAttribute('max');

        expect(Number(min)).toBe(0);
        expect(Number(max)).toBe(100);
    });
});
