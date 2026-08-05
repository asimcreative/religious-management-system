/**
 * RAMS Screenshot Script
 * Takes professional screenshots of all major modules.
 * Usage: node scripts/take-screenshots.cjs
 */

const { chromium } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

const BASE_URL = 'http://127.0.0.1:8088';
const SCREENSHOT_DIR = path.join(__dirname, '..', 'docs', 'screenshots');
const CREDENTIALS = { email: 'admin@demo.test', password: 'DemoAdmin@1234' };
const VIEWPORT = { width: 1440, height: 900 };

if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function saveScreenshot(page, filename) {
    const filepath = path.join(SCREENSHOT_DIR, filename);
    await page.screenshot({ path: filepath, fullPage: false });
    const size = (fs.statSync(filepath).size / 1024).toFixed(0);
    console.log(`  ✓ ${filename} (${size} KB) — ${page.url()}`);
    return filepath;
}

async function goTo(page, url) {
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
    await delay(1000);
}

async function run() {
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    const context = await browser.newContext({
        viewport: VIEWPORT,
        deviceScaleFactor: 1.5,
    });

    const page = await context.newPage();

    try {
        // ── 1. Login Page ─────────────────────────────────────────
        console.log('\n[1/9] Login Page');
        await goTo(page, `${BASE_URL}/login`);
        await saveScreenshot(page, '01-login.png');

        // ── Authenticate ──────────────────────────────────────────
        console.log('\n  → Authenticating...');
        await page.fill('input[name="email"]', CREDENTIALS.email);
        await page.fill('input[name="password"]', CREDENTIALS.password);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle', timeout: 15000 }).catch(() => {}),
            page.click('button[type="submit"]'),
        ]);
        await delay(1500);
        console.log(`  ✓ Authenticated — now at ${page.url()}`);

        // ── 2. Dashboard ─────────────────────────────────────────
        console.log('\n[2/9] Dashboard');
        await goTo(page, `${BASE_URL}/dashboard`);
        await saveScreenshot(page, '02-dashboard.png');

        // ── 3. Employees ─────────────────────────────────────────
        console.log('\n[3/9] Employees');
        await goTo(page, `${BASE_URL}/employees`);
        await saveScreenshot(page, '03-employees.png');

        // ── 4. Teachers ──────────────────────────────────────────
        console.log('\n[4/9] Teachers');
        await goTo(page, `${BASE_URL}/teachers`);
        await saveScreenshot(page, '04-teachers.png');

        // ── 5. Quran Classes ─────────────────────────────────────
        console.log('\n[5/9] Quran Classes');
        await goTo(page, `${BASE_URL}/quran-classes`);
        await saveScreenshot(page, '05-quran-classes.png');

        // ── 6. Quran Progress ─────────────────────────────────────
        console.log('\n[6/9] Quran Progress');
        await goTo(page, `${BASE_URL}/quran-progress`);
        await saveScreenshot(page, '06-quran-progress.png');

        // ── 7. Salah Attendance ──────────────────────────────────
        console.log('\n[7/9] Salah Attendance');
        await goTo(page, `${BASE_URL}/salah-attendance`);
        await saveScreenshot(page, '07-salah-attendance.png');

        // ── 8. Reports ───────────────────────────────────────────
        console.log('\n[8/9] Reports');
        await goTo(page, `${BASE_URL}/reports`);
        await saveScreenshot(page, '08-reports.png');

        // ── 9. Masters / Settings ────────────────────────────────
        console.log('\n[9/9] Masters / Settings');
        await goTo(page, `${BASE_URL}/masters/branches`);
        await saveScreenshot(page, '09-masters-settings.png');

        // ── Summary ──────────────────────────────────────────────
        console.log('\n\nAll screenshots saved to: ' + SCREENSHOT_DIR);
        console.log('\nSummary:');
        const files = fs.readdirSync(SCREENSHOT_DIR)
            .filter(f => f.endsWith('.png') && /^\d/.test(f))
            .sort();
        files.forEach(f => {
            const size = (fs.statSync(path.join(SCREENSHOT_DIR, f)).size / 1024).toFixed(0);
            console.log(`  ${f} — ${size} KB`);
        });

    } catch (err) {
        console.error('\n✗ Fatal Error:', err.message);
        await page.screenshot({ path: path.join(SCREENSHOT_DIR, 'debug-error.png') });
        throw err;
    } finally {
        await browser.close();
    }
}

run().catch(err => {
    console.error(err);
    process.exit(1);
});
