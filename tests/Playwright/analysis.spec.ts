import { test, expect, Page } from '@playwright/test';

/**
 * Report analysis E2E — the breakdown selectors, the filter bar, and export.
 *
 * Requires a seeded database. Credentials come from environment variables:
 *   E2E_EMAIL    (default: admin@demo.test)
 *   E2E_PASSWORD (default: DemoAdmin@1234)
 */

const email = process.env.E2E_EMAIL ?? 'admin@demo.test';
const password = process.env.E2E_PASSWORD ?? 'DemoAdmin@1234';

async function signIn(page: Page) {
    await page.goto('/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('form[action$="/login"] button[type="submit"]');
    await page.waitForURL(/dashboard/);
}

test.describe('Report analysis', () => {
    test.beforeEach(async ({ page }) => {
        await signIn(page);
    });

    test('the analysis centre lists the datasets', async ({ page }) => {
        await page.goto('/reports/analysis');

        await expect(page.locator('a[href$="/reports/analysis/salah-attendance"]')).toBeVisible();
        await expect(page.locator('a[href$="/reports/analysis/quran-attendance"]')).toBeVisible();
        await expect(page.locator('a[href$="/reports/analysis/quran-progress"]')).toBeVisible();
    });

    test('the breakdown selector offers every way of slicing the data', async ({ page }) => {
        await page.goto('/reports/analysis/salah-attendance');

        const groupBy = page.locator('select[name="group_by"]');
        await expect(groupBy).toBeVisible();

        // The whole point of the feature: many breakdowns, not one report.
        const options = await groupBy.locator('option').count();
        expect(options).toBeGreaterThan(10);

        // Grouped under headings so a long list stays navigable.
        expect(await groupBy.locator('optgroup').count()).toBeGreaterThan(1);
    });

    test('a second breakdown can be chosen and excludes the first', async ({ page }) => {
        await page.goto('/reports/analysis/salah-attendance?group_by=prayer');

        const thenBy = page.locator('select[name="then_by"]');

        await expect(thenBy.locator('option[value=""]')).toHaveCount(1);
        await expect(thenBy.locator('option[value="prayer"]')).toHaveCount(0);
    });

    test('changing the breakdown reloads the report', async ({ page }) => {
        await page.goto('/reports/analysis/salah-attendance');

        await page.selectOption('select[name="group_by"]', 'department');
        await page.locator('form.filter-bar button[type="submit"]').click();

        await expect(page).toHaveURL(/group_by=department/);
        await expect(page.locator('select[name="group_by"]')).toHaveValue('department');
    });

    test('every filter the dataset declares is on the page', async ({ page }) => {
        await page.goto('/reports/analysis/salah-attendance');

        const bar = page.locator('form.filter-bar');

        // Person, record, time and salah filters all present together.
        for (const name of ['date_from', 'date_to', 'prayer_id', 'jamaat_id', 'branch_id', 'department_id', 'designation_id', 'gender', 'status']) {
            await expect(bar.locator(`[name="${name}"]`)).toHaveCount(1);
        }
    });

    test('an applied filter shows as a removable chip', async ({ page }) => {
        await page.goto('/reports/analysis/salah-attendance?group_by=prayer&gender=male');

        const chip = page.locator('.filter-chips .chip').first();

        await expect(chip).toBeVisible();
        await expect(chip).toContainText(/male/i);

        // Removing it keeps the breakdown and drops only that filter.
        await chip.click();
        await expect(page).toHaveURL(/group_by=prayer/);
        await expect(page).not.toHaveURL(/gender=/);
    });

    test('the report states what it was filtered by', async ({ page }) => {
        await page.goto('/reports/analysis/salah-attendance?gender=male');

        // docs/14_REPORTS_MODULE.md rule 5.
        const meta = page.locator('.detail-list');
        await expect(meta).toContainText(/generated/i);
        await expect(meta).toContainText(/filter/i);
    });

    test('the export menu downloads the breakdown that is on screen', async ({ page }) => {
        await page.goto('/reports/analysis/salah-attendance?group_by=department');

        // Clicking through the real menu rather than navigating to the export
        // URL: page.goto() on a download throws, and the menu is what has to
        // work anyway. The link must also carry the current breakdown, so the
        // file matches the page rather than the default view.
        await page.getByRole('button', { name: /export/i }).click();

        const csv = page.locator('.dropdown-menu.show a[href*="format=csv"]');
        await expect(csv).toHaveAttribute('href', /group_by=department/);

        const download = page.waitForEvent('download');
        await csv.click();

        expect((await download).suggestedFilename()).toMatch(/\.csv$/);
    });

    test('an unknown dataset is a 404 rather than a server error', async ({ page }) => {
        const response = await page.goto('/reports/analysis/not-a-dataset');

        expect(response?.status()).toBe(404);
    });
});
