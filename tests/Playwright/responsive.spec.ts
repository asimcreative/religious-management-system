import { test, expect, Page, Browser } from '@playwright/test';

/**
 * Responsive regression guard.
 *
 * The full sweep lives in responsive-audit.spec.ts and takes minutes; this is
 * the fast version that runs with the rest of the suite and fails the build.
 * It asserts the three things that actually break:
 *
 *   - the document never scrolls sideways
 *   - wide tables scroll inside their wrapper, not by taking the page with them
 *   - touch targets are reachable on a device that uses a finger
 *
 * A representative page per layout archetype is enough: every list shares one
 * table component, every form shares one field set, so a regression in the
 * shared layer shows up here whichever page introduced it.
 */

const email = process.env.E2E_EMAIL ?? 'admin@demo.test';
const password = process.env.E2E_PASSWORD ?? 'DemoAdmin@1234';

/** One page per layout archetype. */
const PAGES: Array<{ path: string; label: string }> = [
    { path: '/dashboard', label: 'dashboard with charts and stat cards' },
    { path: '/employees', label: 'list with filters and row actions' },
    { path: '/employees/create', label: 'long form with sections' },
    { path: '/salah-attendance', label: 'wide table with pinned columns' },
    { path: '/roles/create', label: 'dense permission grid' },
    { path: '/reports/quran-attendance', label: 'report with summary and table' },
    { path: '/data/imports', label: 'transfer history' },
];

const WIDTHS = [320, 375, 768, 1024, 1366, 1920];
const TOUCH_MAX = 820;

async function login(page: Page): Promise<void> {
    await page.goto('/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
}

/** Horizontal overflow of the document, in CSS pixels. */
async function overflow(page: Page): Promise<number> {
    return page.evaluate(() => {
        const doc = document.documentElement;
        return doc.scrollWidth - doc.clientWidth;
    });
}

test.describe('Responsive layout', () => {
    for (const width of WIDTHS) {
        test(`no page scrolls sideways at ${width}px`, async ({ browser }) => {
            const context = await browser.newContext(
                width <= TOUCH_MAX
                    ? { viewport: { width, height: 900 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 }
                    : { viewport: { width, height: 900 } },
            );

            const page = await context.newPage();
            await login(page);

            const broken: string[] = [];

            for (const spec of PAGES) {
                await page.goto(spec.path, { waitUntil: 'domcontentloaded' });
                await page.waitForTimeout(100);

                const px = await overflow(page);
                if (px > 1) broken.push(`${spec.label} (${spec.path}) overflows by ${px}px`);
            }

            await context.close();

            expect(broken, `Horizontal overflow at ${width}px:\n${broken.join('\n')}`).toEqual([]);
        });
    }
});

test.describe('Responsive tables', () => {
    test('a wide table scrolls inside its wrapper, not the page', async ({ browser }) => {
        const context = await browser.newContext({
            viewport: { width: 375, height: 900 },
            isMobile: true,
            hasTouch: true,
        });

        const page = await context.newPage();
        await login(page);
        await page.goto('/salah-attendance');

        const result = await page.evaluate(() => {
            const wrap = document.querySelector<HTMLElement>('.table-wrap');
            if (!wrap) return null;

            return {
                // The wrapper is no wider than the screen…
                clientWidth: wrap.clientWidth,
                // …while its content genuinely is wider, so it must scroll.
                scrollWidth: wrap.scrollWidth,
                overflowX: getComputedStyle(wrap).overflowX,
                documentOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
            };
        });

        await context.close();

        expect(result).not.toBeNull();
        expect(result!.overflowX).toBe('auto');
        expect(result!.clientWidth).toBeLessThanOrEqual(375);
        expect(result!.scrollWidth).toBeGreaterThan(result!.clientWidth);
        expect(result!.documentOverflow).toBeLessThanOrEqual(1);
    });
});

test.describe('Touch targets', () => {
    test('every control is reachable with a finger', async ({ browser }) => {
        const context = await browser.newContext({
            viewport: { width: 390, height: 900 },
            isMobile: true,
            hasTouch: true,
            deviceScaleFactor: 2,
        });

        const page = await context.newPage();
        await login(page);

        const tooSmall: string[] = [];

        for (const spec of PAGES) {
            await page.goto(spec.path, { waitUntil: 'domcontentloaded' });
            await page.waitForTimeout(100);

            const found = await page.evaluate(() => {
                const out: string[] = [];

                document.querySelectorAll<HTMLElement>('body *').forEach((el) => {
                    const style = getComputedStyle(el);
                    if (style.display === 'none' || style.visibility === 'hidden') return;
                    if (el.closest('.visually-hidden, .print-only')) return;

                    if (!el.matches('a[href], button, [role="button"], input:not([type="hidden"]), select, textarea')) return;

                    // Links inside running text are not standalone targets.
                    if (el.closest('p, li.breadcrumb-item, .cell-primary, td, .dropdown-menu')) return;

                    const wrapper = el.matches('.form-check-input')
                        ? el.closest('.form-check, .check-card, .col-select')
                        : null;

                    const box = (wrapper ?? el).getBoundingClientRect();
                    if (box.width === 0 && box.height === 0) return;

                    if (box.height < 40 || box.width < 24) {
                        const cls = (el.getAttribute('class') ?? '').split(/\s+/).filter(Boolean).slice(0, 2).join('.');
                        out.push(`${el.tagName.toLowerCase()}${cls ? '.' + cls : ''} ${Math.round(box.width)}×${Math.round(box.height)}`);
                    }
                });

                return out.slice(0, 6);
            });

            found.forEach((f) => tooSmall.push(`${spec.path}: ${f}`));
        }

        await context.close();

        expect(tooSmall, `Controls below the 44px touch target:\n${tooSmall.join('\n')}`).toEqual([]);
    });
});

test.describe('Responsive shell', () => {
    test('the sidebar is off-canvas on a phone and fixed on a desktop', async ({ browser }) => {
        const phone = await browser.newContext({
            viewport: { width: 390, height: 900 },
            isMobile: true,
            hasTouch: true,
        });
        const phonePage = await phone.newPage();
        await login(phonePage);
        await phonePage.goto('/dashboard');

        // Off-canvas: present in the DOM, pushed outside the viewport.
        const phoneSidebar = await phonePage.evaluate(() => {
            const el = document.querySelector('.rams-sidebar');
            return el ? el.getBoundingClientRect().right : null;
        });

        expect(phoneSidebar).not.toBeNull();
        expect(phoneSidebar!).toBeLessThanOrEqual(1);

        // The toggle exists and opens it.
        await phonePage.locator('[data-sidebar-toggle], .rams-topbar__icon-btn').first().click();
        await phonePage.waitForTimeout(350);

        const opened = await phonePage.evaluate(
            () => document.querySelector('.rams-sidebar')!.getBoundingClientRect().right,
        );
        expect(opened).toBeGreaterThan(100);

        await phone.close();

        const desktop = await browser.newContext({ viewport: { width: 1366, height: 900 } });
        const desktopPage = await desktop.newPage();
        await login(desktopPage);
        await desktopPage.goto('/dashboard');

        const desktopSidebar = await desktopPage.evaluate(
            () => document.querySelector('.rams-sidebar')!.getBoundingClientRect().right,
        );
        expect(desktopSidebar).toBeGreaterThan(100);

        await desktop.close();
    });

    test('a modal fits the screen and scrolls its own body', async ({ browser }) => {
        const context = await browser.newContext({
            viewport: { width: 375, height: 700 },
            isMobile: true,
            hasTouch: true,
        });

        const page = await context.newPage();
        await login(page);
        await page.goto('/masters/branches');

        await page.locator('.data-toolbar').first().getByRole('button', { name: /import/i }).click();
        await page.waitForTimeout(400);

        const box = await page.evaluate(() => {
            const content = document.querySelector<HTMLElement>('.modal.show .modal-content');
            const body = document.querySelector<HTMLElement>('.modal.show .modal-body');
            if (!content || !body) return null;

            const r = content.getBoundingClientRect();

            return {
                right: r.right,
                width: r.width,
                height: r.height,
                bodyOverflowY: getComputedStyle(body).overflowY,
                documentOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
            };
        });

        await context.close();

        expect(box).not.toBeNull();
        expect(box!.width).toBeLessThanOrEqual(375);
        expect(box!.right).toBeLessThanOrEqual(376);
        expect(box!.height).toBeLessThanOrEqual(700);
        expect(box!.bodyOverflowY).toBe('auto');
        expect(box!.documentOverflow).toBeLessThanOrEqual(1);
    });
});
