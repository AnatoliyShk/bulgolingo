import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

const USER_EMAIL = process.env.E2E_USER_EMAIL ?? 'e2e-stats@example.com';
const USER_PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

// Logs in through the UI; returns false when the seeded user is unavailable
// so callers can skip instead of failing on fixture-less environments.
async function login(page: Page): Promise<boolean> {
    await page.goto(`${BASE}/login`);
    await page.fill('#email', USER_EMAIL);
    await page.fill('#password', USER_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('login'), { timeout: 15000 }).catch(() => {});
    return !page.url().includes('/login');
}

const hero = (page: Page) => page.locator('.nb-prof__hero');
const paths = (page: Page) => page.locator('.nb-prof__section');
const statsLink = (page: Page) => page.locator('.nb-prof__stats');
const settings = (page: Page) => page.locator('.nb-prof__edit-btn');

test.describe('Public profile', () => {
    test('the owner profile answers at /profile', async ({ page }) => {
        test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');

        await page.goto(`${BASE}/profile`);
        await expect(hero(page)).toBeVisible();
        await expect(settings(page)).toBeVisible();
        await expect(paths(page)).toBeVisible();
        await expect(statsLink(page)).toBeVisible();
    });

    test.describe('viewed from the leaderboard', () => {
        // The link is the only way into this page from the UI, so the walk from
        // the leaderboard is what the test drives rather than a typed URL.
        test.beforeEach(async ({ page }) => {
            test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');

            await page.goto(`${BASE}/stats`);
            await page.waitForSelector('.nb-stats__leaderboard-row, .nb-stats__empty', { timeout: 15000 });
        });

        test('every leaderboard entry offers a way to its profile', async ({ page }) => {
            const rows = page.locator('.nb-stats__leaderboard-row');
            test.skip((await rows.count()) === 0, 'no experience earned in this environment');

            for (const row of await rows.all()) {
                await expect(row.getByRole('link', { name: 'View profile' }))
                    .toHaveAttribute('href', /^\/profile\/\d+$/);
            }
        });

        test('following one lands on that profile with the private blocks gone', async ({ page }) => {
            const rows = page.locator('.nb-stats__leaderboard-row');
            test.skip((await rows.count()) === 0, 'no experience earned in this environment');

            await rows.first().getByRole('link', { name: 'View profile' }).click();
            await page.waitForURL(/\/profile\/\d+$/, { timeout: 15000 });

            await expect(hero(page)).toBeVisible();
            await expect(paths(page)).toHaveCount(0);
            await expect(statsLink(page)).toHaveCount(0);
            await expect(settings(page)).toHaveCount(0);
        });
    });

    test('a public profile is readable without logging in', async ({ page }) => {
        // Reached without a session at all, which is what makes it public.
        const response = await page.goto(`${BASE}/profile/1`);

        test.skip(response?.status() === 404, 'no user with id 1 in this environment');

        expect(response?.status()).toBe(200);
        await expect(hero(page)).toBeVisible();
        await expect(settings(page)).toHaveCount(0);
    });

    test('an unknown profile is not found', async ({ page }) => {
        const response = await page.goto(`${BASE}/profile/999999`);

        expect(response?.status()).toBe(404);
    });
});
