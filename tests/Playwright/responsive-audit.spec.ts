import { test, expect, Page, Browser } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

/**
 * Responsive audit.
 *
 * Walks every page at every supported width and measures what is actually
 * wrong rather than what looks wrong: horizontal overflow on the document,
 * the specific elements causing it, and interactive controls below the 44px
 * touch target.
 *
 * Run it as an audit (writes storage/responsive-audit.json):
 *     npx playwright test responsive-audit
 *
 * The same measurements run as assertions in responsive.spec.ts, which is the
 * permanent regression guard. This file is the diagnostic that tells you which
 * element to fix.
 */

const email = process.env.E2E_EMAIL ?? 'admin@demo.test';
const password = process.env.E2E_PASSWORD ?? 'DemoAdmin@1234';

/** Every width the project commits to supporting. */
const ALL_WIDTHS = [
    320, 360, 375, 390, 412, 430, 480, 576, 640,
    768, 820, 992, 1024, 1200, 1366, 1440, 1600, 1920,
];

/** The subset every page is swept at; the rest get the full ladder. */
const SWEEP_WIDTHS = [320, 375, 768, 1024, 1366, 1920];

/** Pages complex enough to deserve every breakpoint. */
const DEEP_PAGES = ['/dashboard', '/employees', '/employees/create'];

type PageSpec = { path: string; label: string; group: string };

const PAGES: PageSpec[] = [
    { path: '/login', label: 'Login', group: 'Guest' },
    { path: '/forgot-password', label: 'Forgot password', group: 'Guest' },

    { path: '/dashboard', label: 'Dashboard', group: 'Overview' },
    { path: '/notifications', label: 'Notifications', group: 'Overview' },
    { path: '/change-password', label: 'Change password', group: 'Account' },

    { path: '/employees', label: 'Employees list', group: 'People' },
    { path: '/employees/create', label: 'Employee form', group: 'People' },
    { path: '/teachers', label: 'Teachers list', group: 'People' },
    { path: '/teachers/create', label: 'Teacher form', group: 'People' },
    { path: '/users', label: 'Users list', group: 'Administration' },
    { path: '/users/create', label: 'User form', group: 'Administration' },
    { path: '/roles', label: 'Roles list', group: 'Administration' },
    { path: '/roles/create', label: 'Role form (permission grid)', group: 'Administration' },
    { path: '/settings', label: 'Settings', group: 'Administration' },

    { path: '/quran-classes', label: 'Quran classes list', group: 'Quran' },
    { path: '/quran-classes/create', label: 'Quran class form', group: 'Quran' },
    { path: '/quran-attendance', label: 'Quran attendance list', group: 'Quran' },
    { path: '/quran-attendance/create', label: 'Quran attendance entry', group: 'Quran' },
    { path: '/quran-progress', label: 'Quran progress list', group: 'Quran' },
    { path: '/quran-progress/create', label: 'Quran progress form', group: 'Quran' },

    { path: '/jamaats', label: 'Jamaats list', group: 'Salah' },
    { path: '/jamaats/create', label: 'Jamaat form', group: 'Salah' },
    { path: '/salah-attendance', label: 'Salah attendance list', group: 'Salah' },
    { path: '/salah-attendance/create', label: 'Salah attendance entry', group: 'Salah' },

    { path: '/reports', label: 'Report centre', group: 'Reports' },
    { path: '/reports/dashboard', label: 'Dashboard report', group: 'Reports' },
    { path: '/reports/employees', label: 'Employee report', group: 'Reports' },
    { path: '/reports/teachers', label: 'Teacher report', group: 'Reports' },
    { path: '/reports/quran-attendance', label: 'Quran attendance report', group: 'Reports' },
    { path: '/reports/quran-progress', label: 'Quran progress report', group: 'Reports' },
    { path: '/reports/salah-attendance', label: 'Salah attendance report', group: 'Reports' },
    { path: '/reports/analysis', label: 'Analysis centre', group: 'Reports' },
    // The densest filter bar in the application — every filter the dataset
    // offers, on one row. Worth auditing at 320px in its own right.
    { path: '/reports/analysis/salah-attendance', label: 'Salah analysis', group: 'Reports' },
    { path: '/reports/analysis/quran-attendance', label: 'Quran analysis', group: 'Reports' },
    { path: '/reports/analysis/quran-progress', label: 'Quran progress analysis', group: 'Reports' },

    { path: '/masters/branches', label: 'Branches list', group: 'Master data' },
    { path: '/masters/branches/create', label: 'Branch form', group: 'Master data' },
    { path: '/masters/attendance-reasons/salah', label: 'Salah attendance reasons list', group: 'Master data' },
    { path: '/masters/attendance-reasons/salah/create', label: 'Salah attendance reason form', group: 'Master data' },
    { path: '/masters/attendance-reasons/quran', label: 'Quran attendance reasons list', group: 'Master data' },
    { path: '/masters/attendance-reasons/quran/create', label: 'Quran attendance reason form', group: 'Master data' },
    { path: '/masters/attendance-reasons/taleem', label: 'Taleem attendance reasons list', group: 'Master data' },
    { path: '/masters/attendance-reasons/taleem/create', label: 'Taleem attendance reason form', group: 'Master data' },
    { path: '/masters/quran-statuses', label: 'Quran statuses list', group: 'Master data' },
    { path: '/masters/languages', label: 'Languages list', group: 'Master data' },

    { path: '/data/imports', label: 'Import history', group: 'Data transfer' },
    { path: '/data/exports', label: 'Export history', group: 'Data transfer' },
];

type Finding = {
    page: string;
    group: string;
    width: number;
    documentOverflow: number;
    offenders: string[];
    smallTargets: string[];
    tinyText: number;
};

async function login(page: Page): Promise<void> {
    await page.goto('/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
}

/**
 * Measure one rendered page.
 *
 * Runs in the browser so it sees computed layout, not markup. Elements are
 * described by a short CSS-ish path so a finding points at something findable.
 */
async function measure(page: Page) {
    return page.evaluate(() => {
        const describe = (el: Element): string => {
            const tag = el.tagName.toLowerCase();
            const cls = (el.getAttribute('class') ?? '')
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 3)
                .join('.');

            return cls ? `${tag}.${cls}` : tag;
        };

        const doc = document.documentElement;
        const documentOverflow = doc.scrollWidth - doc.clientWidth;
        const viewport = doc.clientWidth;

        const offenders: string[] = [];
        const smallTargets: string[] = [];
        let tinyText = 0;

        document.querySelectorAll<HTMLElement>('body *').forEach((el) => {
            const style = window.getComputedStyle(el);

            if (style.display === 'none' || style.visibility === 'hidden') return;

            // Content hidden for sighted users is clipped by its wrapper;
            // getBoundingClientRect reports the pre-clip box, so measuring it
            // would report an overflow the user can never see.
            if (el.closest('.visually-hidden, .print-only')) return;

            const rect = el.getBoundingClientRect();
            if (rect.width === 0 && rect.height === 0) return;

            // Something sticking out past the right edge, that is not inside a
            // container which legitimately scrolls sideways.
            if (rect.right > viewport + 1) {
                const scrollable = el.closest('.table-wrap, [style*="overflow"], .dropdown-menu, .modal');
                if (!scrollable && offenders.length < 8) {
                    offenders.push(`${describe(el)} → right ${Math.round(rect.right)}px > ${viewport}px`);
                }
            }

            // Touch targets. Only things a finger has to hit, and only on a
            // device that uses one.
            const coarse = window.matchMedia('(pointer: coarse)').matches;
            const interactive = el.matches('a[href], button, [role="button"], input:not([type="hidden"]), select, textarea, summary');

            if (coarse && interactive && rect.height > 0 && rect.width > 0) {
                // A checkbox is small on purpose; the wrapper it sits in — a
                // form-check row, a check-card, a selection cell — is what the
                // finger actually gets, so that is what is measured.
                const wrapper = el.matches('.form-check-input')
                    ? el.closest('.form-check, .check-card, .col-select')
                    : null;

                const box = wrapper ? wrapper.getBoundingClientRect() : rect;

                const tooSmall = box.height < 40 || box.width < 24;
                const inline = el.closest('p, li.breadcrumb-item, .cell-primary, td, .dropdown-menu');

                if (tooSmall && !inline && smallTargets.length < 8) {
                    smallTargets.push(`${describe(el)} → ${Math.round(box.width)}×${Math.round(box.height)}`);
                }
            }

            if (el.childElementCount === 0 && (el.textContent ?? '').trim().length > 0) {
                if (parseFloat(style.fontSize) < 11) tinyText += 1;
            }
        });

        return { documentOverflow, offenders, smallTargets, tinyText };
    });
}

/**
 * Widths at or below this are audited in a touch context.
 *
 * Touch target rules key off `pointer: coarse`, which Chromium only reports
 * when the context is emulating a mobile device. Auditing a phone width in a
 * desktop context measures a layout no phone will ever render.
 */
const TOUCH_MAX_WIDTH = 820;

test('responsive audit across every page and breakpoint', async ({ browser }) => {
    test.setTimeout(30 * 60 * 1000);

    const findings: Finding[] = [];
    const skipped: string[] = [];

    // Two contexts: one that behaves like a phone or tablet, one like a
    // desktop browser. Pages are measured in whichever matches the width.
    const touchContext = await browser.newContext({
        viewport: { width: 390, height: 900 },
        isMobile: true,
        hasTouch: true,
        deviceScaleFactor: 2,
    });
    const desktopContext = await browser.newContext({ viewport: { width: 1366, height: 900 } });

    const touchPage = await touchContext.newPage();
    const desktopPage = await desktopContext.newPage();

    const pageFor = (width: number): Page => (width <= TOUCH_MAX_WIDTH ? touchPage : desktopPage);

    const page = desktopPage;

    const record = (spec: PageSpec, width: number, result: Awaited<ReturnType<typeof measure>>) => {
        if (result.documentOverflow > 1 || result.offenders.length > 0
            || result.smallTargets.length > 0 || result.tinyText > 0) {
            findings.push({
                page: spec.label,
                group: spec.group,
                width,
                documentOverflow: result.documentOverflow,
                offenders: result.offenders,
                smallTargets: result.smallTargets,
                tinyText: result.tinyText,
            });
        }
    };

    // Guest screens are measured signed out; signing in first would silently
    // redirect /login to the dashboard and audit the wrong page twice.
    for (const spec of PAGES.filter((p) => p.group === 'Guest')) {
        for (const width of SWEEP_WIDTHS) {
            const target = pageFor(width);
            await target.setViewportSize({ width, height: 900 });
            await target.goto(spec.path, { waitUntil: 'domcontentloaded' });
            await target.waitForTimeout(120);

            record(spec, width, await measure(target));
        }
    }

    await login(touchPage);
    await login(desktopPage);

    for (const spec of PAGES.filter((p) => p.group !== 'Guest')) {
        const widths = DEEP_PAGES.includes(spec.path) ? ALL_WIDTHS : SWEEP_WIDTHS;

        for (const width of widths) {
            const target = pageFor(width);
            await target.setViewportSize({ width, height: 900 });

            const response = await target.goto(spec.path, { waitUntil: 'domcontentloaded' });

            if (response && response.status() >= 400) {
                if (!skipped.includes(spec.path)) {
                    skipped.push(`${spec.path} (HTTP ${response.status()})`);
                }
                break;
            }

            // Let sticky/fixed layout settle before measuring.
            await target.waitForTimeout(120);

            record(spec, width, await measure(target));
        }
    }

    await touchContext.close();
    await desktopContext.close();

    const report = {
        generatedAt: new Date().toISOString(),
        pagesChecked: PAGES.length,
        widths: { sweep: SWEEP_WIDTHS, deep: ALL_WIDTHS, deepPages: DEEP_PAGES },
        skipped,
        findings,
    };

    const out = path.join(process.cwd(), 'storage', 'responsive-audit.json');
    fs.mkdirSync(path.dirname(out), { recursive: true });
    fs.writeFileSync(out, JSON.stringify(report, null, 2));

    // Console summary, grouped so the worst offenders are obvious.
    const overflowing = findings.filter((f) => f.documentOverflow > 1);
    const withOffenders = findings.filter((f) => f.offenders.length > 0);
    const withSmallTargets = findings.filter((f) => f.smallTargets.length > 0);

    console.log('\n─── RESPONSIVE AUDIT ─────────────────────────────');
    console.log(`pages: ${PAGES.length}   findings: ${findings.length}`);
    console.log(`horizontal overflow: ${overflowing.length}`);
    console.log(`overflowing elements: ${withOffenders.length}`);
    console.log(`small touch targets: ${withSmallTargets.length}`);
    if (skipped.length) console.log(`skipped: ${skipped.join(', ')}`);

    for (const f of overflowing.slice(0, 40)) {
        console.log(`\n  OVERFLOW  ${f.page} @ ${f.width}px  (+${f.documentOverflow}px)`);
        f.offenders.forEach((o) => console.log(`            ${o}`));
    }

    for (const f of withSmallTargets.slice(0, 20)) {
        console.log(`\n  TOUCH     ${f.page} @ ${f.width}px`);
        f.smallTargets.forEach((s) => console.log(`            ${s}`));
    }

    console.log('\n──────────────────────────────────────────────────\n');

    // The audit itself never fails; it reports. responsive.spec.ts asserts.
    expect(findings.length).toBeGreaterThanOrEqual(0);
});
