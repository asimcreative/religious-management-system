import { defineConfig, devices } from '@playwright/test';

/**
 * RAMS — Playwright E2E Configuration
 *
 * Targets the local Laravel dev server.
 * Set BASE_URL env var to override (e.g. staging).
 *
 * Run:  npx playwright test
 * UI:   npx playwright test --ui
 * Head: npx playwright test --headed
 */
export default defineConfig({
    testDir: './tests/Playwright',
    timeout: 30_000,
    expect: { timeout: 5_000 },
    fullyParallel: false, // sequential — shared DB state
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: [
        ['list'],
        ['html', { outputFolder: 'storage/playwright-report', open: 'never' }],
    ],

    use: {
        baseURL: process.env.BASE_URL ?? 'http://127.0.0.1:8000',
        headless: true,
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        trace: 'retain-on-failure',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    // Start `php artisan serve` automatically when not in CI.
    // In CI, the server should already be running.
    webServer: process.env.CI
        ? undefined
        : {
              command: 'php artisan serve --port=8000',
              url: 'http://127.0.0.1:8000',
              reuseExistingServer: true,
              timeout: 30_000,
          },
});
