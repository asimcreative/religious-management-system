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

});

// Guest-only checks must NOT live in a describe that logs in via beforeEach,
// otherwise the session is already authenticated and the assertion is void.
test.describe('Attendance guest access', () => {
    test('unauthenticated access to quran attendance redirects to login', async ({ page }) => {
        await page.goto('/quran-attendance');
        await expect(page).toHaveURL(/login/);
    });

    test('unauthenticated access to salah attendance redirects to login', async ({ page }) => {
        await page.goto('/salah-attendance');
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

/**
 * Teacher/qari absence toggle — checking it must suspend the per-student
 * grid (nobody can be marked absent for a class that did not happen) and
 * require a reason, and both must persist across a reload. This is the only
 * automatable seam for the flow: the mark-attendance screen is server-rendered
 * Blade, so the checkbox/reason/lock behaviour here IS the contract a native
 * Tutor-App-style client would also have to honour.
 */
test.describe('Quran attendance — teacher absent toggle', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('checking teacher absent requires a reason and locks the student grid', async ({ page }) => {
        await page.goto('/quran-attendance/create');

        const classSelect = page.locator('select[name="class_id"]');
        const options = classSelect.locator('option');

        if (await options.count() < 2) {
            test.skip(true, 'No class to load — nothing to mark attendance for.');
        }

        await classSelect.selectOption({ index: 1 });
        await page.locator('button[type="submit"]', { hasText: /load/i }).first().click();
        await page.waitForLoadState('networkidle');

        const toggle = page.locator('[data-teacher-absent-toggle]');

        if (await toggle.count() === 0) {
            test.skip(true, 'No members loaded for this class/date — the sheet did not render.');
        }

        if (await toggle.isDisabled()) {
            test.skip(true, 'This class has no assigned teacher — the toggle is intentionally disabled.');
        }

        const reasonField = page.locator('#teacher_absence_reason_id');
        const sheet = page.locator('[data-attendance-sheet]');

        await expect(reasonField).toBeHidden();

        await toggle.check();

        await expect(reasonField).toBeVisible();
        await expect(reasonField).toHaveAttribute('required', '');
        await expect(sheet).toHaveClass(/attendance-sheet--suspended/);

        await toggle.uncheck();

        await expect(reasonField).toBeHidden();
        await expect(sheet).not.toHaveClass(/attendance-sheet--suspended/);
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

    /**
     * Salah attendance is recorded for ALL prayers in a single submission:
     * the roster is a grid of members x prayers named
     * `attendance[employee_id][prayer_id]`. There is deliberately no single
     * `prayer_id` selector — asserting its absence guards the contract, which
     * the controller, the service and the Feature tests all depend on.
     */
    test('create page uses the all-prayers grid, not a single prayer selector', async ({ page }) => {
        await page.goto('/salah-attendance/create');

        await expect(page.locator('select[name="prayer_id"]')).toHaveCount(0);
        await expect(page.locator('select[name="jamaat_id"]').first()).toBeVisible();
    });
});

// ── Attendance reasons ─────────────────────────────────────────────────────

/**
 * Presence is stored as a NULL reason and every other status is a row in the
 * per-company attendance_reasons table. Production ran with that table empty:
 * the sheet rendered, the dropdowns opened, and "Present" was the only choice
 * anyone could make — so absences went unrecorded and the module looked healthy
 * while doing nothing. Neither state is an error page, which is why the Feature
 * tests alone did not catch it and why this check reads the rendered control.
 */
test.describe('Attendance reasons are usable from the sheet', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('the status dropdown offers more than Present', async ({ page }) => {
        await page.goto('/salah-attendance/create');

        // The per-member grid only renders once a jamaat is chosen; the bulk
        // setter carries the same options and is present as soon as one is.
        const bulkStatus = page.locator('#bulkReason');

        if (await bulkStatus.count() === 0) {
            const jamaat = page.locator('select[name="jamaat_id"]');
            const options = jamaat.locator('option');

            if (await options.count() < 2) {
                test.skip(true, 'No jamaat to load — nothing to mark attendance for.');
            }

            await jamaat.selectOption({ index: 1 });
            await page.waitForLoadState('networkidle');
        }

        await expect(bulkStatus).toBeVisible();
        expect(await bulkStatus.locator('option').count()).toBeGreaterThan(1);
    });

    test('a company with no reasons is told so instead of being left with Present', async ({ page }) => {
        await page.goto('/salah-attendance/create');

        const warning = page.getByText('Only "Present" can be recorded');
        const bulkStatus = page.locator('#bulkReason');

        // Exactly one of the two must hold: either reasons exist and the sheet
        // is usable, or they do not and the sheet says why.
        if (await warning.count() > 0) {
            await expect(warning.first()).toBeVisible();
            await expect(page.getByRole('link', { name: /attendance reasons/i }).first()).toBeVisible();
        } else if (await bulkStatus.count() > 0) {
            expect(await bulkStatus.locator('option').count()).toBeGreaterThan(1);
        }
    });

    test('the salah reasons master page lists at least one reason', async ({ page }) => {
        await page.goto('/masters/salah-attendance-reasons');

        await expect(page.locator('body')).not.toContainText('500');
        await expect(page.locator('table tbody tr').first()).toBeVisible();
    });

    test('the quran reasons master page lists at least one reason', async ({ page }) => {
        await page.goto('/masters/quran-attendance-reasons');

        await expect(page.locator('body')).not.toContainText('500');
        await expect(page.locator('table tbody tr').first()).toBeVisible();
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
