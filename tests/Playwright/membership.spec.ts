import { test, expect, Page } from '@playwright/test';

/**
 * One active jamaat, one active Quran class — proved against the rendered page.
 *
 * The add-member dropdown used to exclude only the members of the jamaat being
 * viewed, so a member of another jamaat was offered and picking them moved them
 * silently. Nothing on either screen said so, which is precisely why this needs
 * a browser check: the failure looked like a successful add.
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

/** Hrefs of the member-management pages listed on an index. */
async function memberPages(page: Page, indexPath: string, hrefPattern: RegExp): Promise<string[]> {
    await page.goto(indexPath);

    const hrefs = await page.locator(`a[href*="/members"]`).evaluateAll(
        (links) => links.map((link) => (link as HTMLAnchorElement).getAttribute('href') ?? ''),
    );

    return [...new Set(hrefs.filter((href) => hrefPattern.test(href)))];
}

/** Names in the add-member dropdown, stripped of the "(CODE)" suffix. */
async function offeredNames(page: Page): Promise<string[]> {
    const options = page.locator('select[name="employee_id"] option');

    if (await options.count() === 0) {
        return [];
    }

    const labels = await options.allTextContents();

    return labels
        .map((label) => label.replace(/\s*\([^)]*\)\s*$/, '').trim())
        .filter((label) => label.length > 0 && !label.startsWith('--'));
}

/** Names in the active-member table. */
async function currentMembers(page: Page): Promise<string[]> {
    const cells = page.locator('table tbody tr .cell-primary__title');

    return (await cells.allTextContents()).map((name) => name.trim()).filter(Boolean);
}

async function assertMembersAreNotOfferedElsewhere(
    page: Page,
    indexPath: string,
    hrefPattern: RegExp,
): Promise<void> {
    const pages = await memberPages(page, indexPath, hrefPattern);

    if (pages.length < 2) {
        test.skip(true, `Fewer than two groups at ${indexPath} — nothing to compare.`);
    }

    await page.goto(pages[0]);
    const held = await currentMembers(page);

    if (held.length === 0) {
        test.skip(true, 'The first group has no members — nothing that could leak.');
    }

    await page.goto(pages[1]);
    const offered = await offeredNames(page);

    for (const name of held) {
        expect(offered, `${name} belongs to another group and must not be offered here`)
            .not.toContain(name);
    }
}

test.describe('Jamaat membership', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('a member of one jamaat is not offered to another', async ({ page }) => {
        await assertMembersAreNotOfferedElsewhere(page, '/jamaats', /\/jamaats\/\d+\/members/);
    });

    test('the add form explains who is listed', async ({ page }) => {
        const pages = await memberPages(page, '/jamaats', /\/jamaats\/\d+\/members/);

        if (pages.length === 0) {
            test.skip(true, 'No jamaat to open.');
        }

        await page.goto(pages[0]);
        await expect(page.locator('body')).not.toContainText('500');

        // Either the form is there with its help text, or the empty state says
        // why it is not. Both sentences now describe what the query does.
        const hasForm = await page.locator('select[name="employee_id"]').count() > 0;

        if (hasForm) {
            await expect(page.getByText(/not already in a jamaat/i).first()).toBeVisible();
        } else {
            await expect(page.getByText(/already belongs to a jamaat/i).first()).toBeVisible();
        }
    });
});

test.describe('Quran class membership', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('a member of one class is not offered to another', async ({ page }) => {
        await assertMembersAreNotOfferedElsewhere(page, '/quran-classes', /\/quran-classes\/\d+\/members/);
    });
});
