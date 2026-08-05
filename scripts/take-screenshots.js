/**
 * RAMS Screenshot Script
 * Takes professional screenshots of all major modules.
 * Usage: node scripts/take-screenshots.js
 */

const { chromium } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

const BASE_URL = 'http://127.0.0.1:8000';
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
    console.log(`  ✓ ${filename} (${size} KB)`);
    return filepath;
}

async function waitForPageLoad(page) {
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await delay(1000);
}

async function login(context) {
    console.log('\n[1/9] Login Page — screenshotting before login');
    const page = await context.newPage();
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded' });
    await waitForPageLoad(page);
    await saveScreenshot(page, '01-login.png');

    console.log('  → logging in...');
    await page.fill('input[name="email"]', CREDENTIALS.email);
    await page.fill('input[name="password"]', CREDENTIALS.password);
    await page.click('button[type="submit"]');
    await waitForPageLoad(page);
    console.log('  ✓ authenticated');
    return page;
}

async function run() {
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    const context = await browser.newContext({
        viewport: VIEWPORT,
        deviceScaleFactor: 1.25,
    });

    try {
        const page = await login(context);

        // ── 2. Dashboard ─────────────────────────────────────────
        console.log('\n[2/9] Dashboard');
        await page.goto(`${BASE_URL}/dashboard`, { waitUntil: 'domcontentloaded' });
        await waitForPageLoad(page);
        await saveScreenshot(page, '02-dashboard.png');

        // ── 3. Employees ─────────────────────────────────────────
        console.log('\n[3/9] Employees');
        await page.goto(`${BASE_URL}/employees`, { waitUntil: 'domcontentloaded' });
        await waitForPageLoad(page);
        await saveScreenshot(page, '03-employees.png');

        // ── 4. Teachers ──────────────────────────────────────────
        console.log('\n[4/9] Teachers');
        await page.goto(`${BASE_URL}/teachers`, { waitUntil: 'domcontentloaded' });
        await waitForPageLoad(page);
        await saveScreenshot(page, '04-teachers.png');

        // ── 5. Quran Classes ─────────────────────────────────────
        console.log('\n[5/9] Quran Classes');
        await page.goto(`${BASE_URL}/quran-classes`, { waitUntil: 'domcontentloaded' });
        await waitForPageLoad(page);
        await saveScreenshot(page, '05-quran-classes.png');

        // ── 6. Quran Progress ─────────────────────────────────────
        console.log('\n[6/9] Quran Progress');
        await page.goto(`${BASE_URL}/quran-progress`, { waitUntil: 'domcontentloaded' });
        await waitForPageLoad(page);
        await saveScreenshot(page, '06-quran-progress.png');

        // ── 7. Salah Attendance ──────────────────────────────────
        console.log('\n[7/9] Salah Attendance');
        await page.goto(`${BASE_URL}/salah-attendance`, { waitUntil: 'domcontentloaded' });
        await waitForPageLoad(page);
        await saveScreenshot(page, '07-salah-attendance.png');

        // ── 8. Reports ───────────────────────────────────────────
        console.log('\n[8/9] Reports Dashboard');
        await page.goto(`${BASE_URL}/reports`, { waitUntil: 'domcontentloaded' });
        await waitForPageLoad(page);
        await saveScreenshot(page, '08-reports.png');

        // ── 9. Masters / Settings ────────────────────────────────
        console.log('\n[9/9] Masters / Branches (Settings)');
        await page.goto(`${BASE_URL}/masters/branches`, { waitUntil: 'domcontentloaded' });
        await waitForPageLoad(page);
        await saveScreenshot(page, '09-masters-branches.png');

        console.log('\n\nAll screenshots saved to: ' + SCREENSHOT_DIR);
        console.log('\nSummary:');
        fs.readdirSync(SCREENSHOT_DIR)
            .filter(f => f.endsWith('.png') && !f.startsWith('error'))
            .sort()
            .forEach(f => {
                const size = (fs.statSync(path.join(SCREENSHOT_DIR, f)).size / 1024).toFixed(0);
                console.log(`  ${f} — ${size} KB`);
            });

    } catch (err) {
        console.error('\n✗ Error:', err.message);
        if (err.stack) console.error(err.stack);
        process.exit(1);
    } finally {
        await browser.close();
    }
}

run();
