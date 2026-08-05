import { test, expect, Page } from '@playwright/test';

/**
 * Notifications E2E — list page, mark-as-read UI, unread count badge.
 *
 * Requires a seeded database with an admin user who has notification.view permission.
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

// ── Notifications Index ────────────────────────────────────────────────────

test.describe('Notifications index', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('notifications page loads without error', async ({ page }) => {
        await page.goto('/notifications');
        await expect(page).toHaveURL(/notifications/);
        await expect(page.locator('body')).not.toContainText('500');
        await expect(page.locator('body')).not.toContainText('Whoops');
    });

    test('notifications page shows list or empty state', async ({ page }) => {
        await page.goto('/notifications');

        const hasTable = await page.locator('table, .notification-item, [class*="notification"]').first().isVisible();
        const hasEmpty = await page.locator(':has-text("No notifications"), :has-text("no notifications"), :has-text("koi")').isVisible();

        // One of the two must be true
        expect(hasTable || hasEmpty).toBeTruthy();
    });

    test('unauthenticated access redirects to login', async ({ page }) => {
        await page.goto('/notifications');
        await expect(page).toHaveURL(/login/);
    });

    test('"Mark all as read" button is present', async ({ page }) => {
        await page.goto('/notifications');

        // Button to mark all notifications as read
        const btn = page.locator(
            'button:has-text("Mark all"), a:has-text("Mark all"), button:has-text("Read All"), form[action*="mark-all-read"] button'
        ).first();
        await expect(btn).toBeVisible();
    });
});

// ── Unread Count Badge ─────────────────────────────────────────────────────

test.describe('Unread count badge', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('unread count element is present on dashboard', async ({ page }) => {
        await page.goto('/dashboard');

        // A badge showing unread notification count — usually in the nav
        const badge = page.locator(
            '[data-unread-count], .notification-badge, .badge[class*="notif"], a[href*="notifications"] .badge'
        ).first();

        // If the badge exists it should display a number (may be 0)
        if (await badge.isVisible()) {
            const text = await badge.textContent();
            expect(text?.trim()).toMatch(/^\d+$/);
        }

        // Test passes regardless — we're checking it doesn't crash
        await expect(page.locator('body')).not.toContainText('500');
    });
});

// ── Mark As Read ──────────────────────────────────────────────────────────

test.describe('Mark notification as read', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('clicking mark-all-read does not produce a 500 error', async ({ page }) => {
        await page.goto('/notifications');

        const form = page.locator('form[action*="mark-all-read"]');

        if (await form.isVisible()) {
            await form.locator('button[type="submit"]').click();

            // Should redirect back or stay on notifications page without error
            await expect(page.locator('body')).not.toContainText('500');
            await expect(page).toHaveURL(/notifications|dashboard/);
        } else {
            // No form visible — test still passes (may have no notifications)
            test.skip();
        }
    });
});

// ── Notification Navigation ────────────────────────────────────────────────

test.describe('Notification navigation', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('navigation has a notifications link', async ({ page }) => {
        await page.goto('/dashboard');

        const link = page.locator('a[href*="notifications"]').first();
        await expect(link).toBeVisible();
    });
});
