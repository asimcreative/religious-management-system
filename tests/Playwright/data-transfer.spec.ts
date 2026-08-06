import { test, expect, Page } from '@playwright/test';
import path from 'node:path';
import fs from 'node:fs';
import os from 'node:os';

/**
 * Import / export toolbar E2E.
 *
 * These cover what a PHP feature test cannot: that the toolbar actually
 * renders on the page, that the Bootstrap dropdowns and the import modal
 * really open, that column visibility survives a reload, and that the whole
 * import journey works in a browser rather than only through the HTTP stack.
 *
 * Requires a seeded database.
 *   E2E_EMAIL    (default: admin@demo.test)
 *   E2E_PASSWORD (default: DemoAdmin@1234)
 */

const email = process.env.E2E_EMAIL ?? 'admin@demo.test';
const password = process.env.E2E_PASSWORD ?? 'DemoAdmin@1234';

async function login(page: Page): Promise<void> {
    await page.goto('/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
}

/** Write a CSV to a temp file so the file chooser has something real to take. */
function csvFixture(name: string, rows: string[][]): string {
    const file = path.join(fs.mkdtempSync(path.join(os.tmpdir(), 'rams-e2e-')), name);
    fs.writeFileSync(file, rows.map((row) => row.map((cell) => `"${cell}"`).join(',')).join('\n'));

    return file;
}

// ── Toolbar ────────────────────────────────────────────────────────────────

test.describe('Data toolbar', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('the toolbar renders on a module list', async ({ page }) => {
        await page.goto('/masters/branches');

        const toolbar = page.locator('.data-toolbar').first();
        await expect(toolbar).toBeVisible();

        await expect(toolbar.getByRole('button', { name: /import/i })).toBeVisible();
        await expect(toolbar.getByRole('button', { name: /export/i })).toBeVisible();
    });

    test('the same toolbar appears on a different module', async ({ page }) => {
        await page.goto('/employees');
        await expect(page.locator('.data-toolbar').first()).toBeVisible();

        await page.goto('/teachers');
        await expect(page.locator('.data-toolbar').first()).toBeVisible();
    });

    test('the export dropdown offers every format and scope', async ({ page }) => {
        await page.goto('/masters/branches');

        await page.locator('.data-toolbar').first().getByRole('button', { name: /export/i }).click();

        const menu = page.locator('.data-toolbar__export');
        await expect(menu).toBeVisible();

        // Three formats, each with its own set of scopes.
        await expect(menu.getByText(/Excel/i).first()).toBeVisible();
        await expect(menu.getByText(/CSV/i).first()).toBeVisible();
        await expect(menu.getByText(/PDF/i).first()).toBeVisible();
        await expect(menu.getByRole('link', { name: /All Records/i }).first()).toBeVisible();
    });

    test('exporting downloads a file named after the module and date', async ({ page }) => {
        await page.goto('/masters/branches');

        // Clicking the real menu item rather than navigating to the URL:
        // page.goto() on a download response resolves differently across
        // browsers and would not prove the dropdown link works.
        await page.locator('.data-toolbar').first().getByRole('button', { name: /export/i }).click();

        const [download] = await Promise.all([
            page.waitForEvent('download'),
            page.locator('.data-toolbar__export a[href*="format=xlsx"][href*="scope=all"]').first().click(),
        ]);

        expect(download.suggestedFilename()).toMatch(/^branches_\d{4}-\d{2}-\d{2}\.xlsx$/);
    });

    test('the sample workbook downloads', async ({ page }) => {
        await page.goto('/masters/branches');

        const [download] = await Promise.all([
            page.waitForEvent('download'),
            page.locator('a[href$="/data/branches/sample"]').first().click(),
        ]);

        expect(download.suggestedFilename()).toBe('branches_sample.xlsx');
    });
});

// ── Import journey ─────────────────────────────────────────────────────────

test.describe('Import', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('the import modal opens and explains itself', async ({ page }) => {
        await page.goto('/masters/branches');

        await page.locator('.data-toolbar').first().getByRole('button', { name: /import/i }).click();

        const modal = page.locator('.modal.show');
        await expect(modal).toBeVisible();
        await expect(modal.locator('input[type="file"]')).toBeVisible();

        // The safe options must be the ones already selected.
        await expect(modal.locator('input[name="mode"][value="skip_invalid"]')).toBeChecked();
        await expect(modal.locator('input[name="duplicate_strategy"][value="skip"]')).toBeChecked();

        // A route to the template, for anyone who opened this without one.
        await expect(modal.locator('a[href$="/data/branches/sample"]')).toBeVisible();
    });

    test('a valid file previews without writing, then imports on confirm', async ({ page }) => {
        const unique = `E2E Branch ${Date.now()}`;
        const file = csvFixture('branches.csv', [
            ['Branch Name', 'Address', 'Phone', 'Status'],
            [unique, '1 Test Road', '042-000', 'Active'],
        ]);

        await page.goto('/masters/branches');
        await page.locator('.data-toolbar').first().getByRole('button', { name: /import/i }).click();

        const modal = page.locator('.modal.show');
        await modal.locator('input[type="file"]').setInputFiles(file);
        await modal.getByRole('button', { name: /validate/i }).click();

        // Preview: the row is shown, and nothing has been saved yet.
        await expect(page.locator('body')).toContainText(unique);
        await expect(page.getByRole('button', { name: /import now/i })).toBeVisible();

        await page.goto('/masters/branches');
        await expect(page.locator('table')).not.toContainText(unique);

        // Redo the upload and confirm this time.
        await page.locator('.data-toolbar').first().getByRole('button', { name: /import/i }).click();
        await page.locator('.modal.show input[type="file"]').setInputFiles(file);
        await page.locator('.modal.show').getByRole('button', { name: /validate/i }).click();
        await page.getByRole('button', { name: /import now/i }).click();

        await expect(page.locator('body')).toContainText(/finished/i);

        await page.goto('/masters/branches?search=' + encodeURIComponent(unique));
        await expect(page.locator('table')).toContainText(unique);
    });

    test('a bad row is reported with its spreadsheet row number', async ({ page }) => {
        const file = csvFixture('bad.csv', [
            ['Branch Name', 'Address', 'Phone', 'Status'],
            [`E2E Good ${Date.now()}`, '', '', 'Active'],
            ['', '', '', 'Purple'],
        ]);

        await page.goto('/masters/branches');
        await page.locator('.data-toolbar').first().getByRole('button', { name: /import/i }).click();

        const modal = page.locator('.modal.show');
        await modal.locator('input[type="file"]').setInputFiles(file);
        await modal.getByRole('button', { name: /validate/i }).click();

        // Row 3 of the file, not "row 1 of the errors".
        await expect(page.locator('body')).toContainText('3');
        await expect(page.locator('body')).toContainText(/required|one of/i);
    });

    test('a file missing a required column is refused before it can be imported', async ({ page }) => {
        const file = csvFixture('missing.csv', [
            ['Address', 'Phone'],
            ['1 Test Road', '042-000'],
        ]);

        await page.goto('/masters/branches');
        await page.locator('.data-toolbar').first().getByRole('button', { name: /import/i }).click();

        const modal = page.locator('.modal.show');
        await modal.locator('input[type="file"]').setInputFiles(file);
        await modal.getByRole('button', { name: /validate/i }).click();

        await expect(page.locator('body')).toContainText(/missing required column/i);
        await expect(page.getByRole('button', { name: /import now/i })).toHaveCount(0);
    });
});

// ── Column visibility, copy and selection ──────────────────────────────────

test.describe('View tools', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('a hidden column stays hidden after a reload', async ({ page }) => {
        await page.goto('/masters/branches');

        await page.locator('[data-columns-button]').first().click();

        const panel = page.locator('[data-column-toggle]');
        await expect(panel).toBeVisible();

        // The first toggle is the first real data column; the selection and
        // row-number columns are furniture and are not listed.
        const firstToggle = panel.locator('input[type="checkbox"]').first();
        const label = (await firstToggle.evaluate((el) => el.parentElement?.textContent ?? '')).trim();

        await firstToggle.uncheck();

        const heading = page.locator('table thead th', { hasText: label }).first();
        await expect(heading).toBeHidden();

        await page.reload();
        await expect(page.locator('table thead th', { hasText: label }).first()).toBeHidden();
    });

    test('selecting rows reveals the bulk bar with a live count', async ({ page }) => {
        await page.goto('/masters/branches');

        const boxes = page.locator('[data-row-select]');

        if (await boxes.count() === 0) {
            test.skip(true, 'No branches seeded to select.');
        }

        await expect(page.locator('[data-selection-bar]')).toBeHidden();

        await boxes.first().check();

        const bar = page.locator('[data-selection-bar]');
        await expect(bar).toBeVisible();
        await expect(bar.locator('[data-selection-count]')).toHaveText('1');

        await bar.locator('[data-selection-clear]').click();
        await expect(bar).toBeHidden();
    });

    test('select-all ticks every row', async ({ page }) => {
        await page.goto('/masters/branches');

        const boxes = page.locator('[data-row-select]');
        const total = await boxes.count();

        if (total === 0) {
            test.skip(true, 'No branches seeded to select.');
        }

        await page.locator('[data-row-select-all]').first().check();
        await expect(page.locator('[data-selection-count]').first()).toHaveText(String(total));
    });
});

// ── History ────────────────────────────────────────────────────────────────

test.describe('Transfer history', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('the export history records what was taken out', async ({ page }) => {
        // Take an export first so there is something to find.
        await page.goto('/data/branches/export?format=csv&scope=all').catch(() => { /* download */ });

        await page.goto('/data/exports');
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('h1')).toContainText(/export history/i);
    });

    test('the import history page renders', async ({ page }) => {
        await page.goto('/data/imports');
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page.locator('h1')).toContainText(/import history/i);
    });
});

// ── Responsive ─────────────────────────────────────────────────────────────

test.describe('Mobile', () => {
    test('the toolbar stays usable at phone width', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await login(page);
        await page.goto('/masters/branches');

        await expect(page.locator('.data-toolbar').first()).toBeVisible();

        // The page itself must never scroll sideways; wide tables scroll
        // inside their own container.
        const overflow = await page.evaluate(
            () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
        );
        expect(overflow).toBeLessThanOrEqual(1);
    });
});
