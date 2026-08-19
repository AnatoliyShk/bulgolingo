import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded credentials (database/seeders/DatabaseSeeder.php); override via env
// when running against an environment with different fixtures.
const EMAIL = process.env.E2E_USER_EMAIL ?? 'test@example.com';
const PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

// Logs in through the UI; returns false when the seeded user is unavailable
// so callers can skip instead of failing on fixture-less environments.
async function login(page: Page): Promise<boolean> {
    await page.goto(`${BASE}/login`);
    await page.fill('#email', EMAIL);
    await page.fill('#password', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('login'), { timeout: 15000 }).catch(() => {});
    return !page.url().includes('/login');
}

test.describe('Profile / dashboard page', () => {
    test.describe('when authenticated', () => {
        test.beforeEach(async ({ page }) => {
            test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');
        });

        test('mobile viewport collapses nav behind a hamburger toggle', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 800 });
            await page.goto(`${BASE}/dashboard`);

            const hamburger = page.locator('.nb-prof__hamburger');
            const toggle = page.locator('.nb-prof__theme-btn');
            const actions = page.locator('.nb-prof__nav-actions');

            await expect(hamburger).toBeVisible();
            await expect(toggle).toBeVisible();
            await expect(actions).not.toBeVisible();

            await hamburger.click();
            await expect(actions).toBeVisible();
            await expect(page.getByRole('button', { name: 'Log out' })).toBeVisible();

            await hamburger.click();
            await expect(actions).not.toBeVisible();
        });

        test('desktop viewport shows actions inline without a hamburger', async ({ page }) => {
            await page.setViewportSize({ width: 1280, height: 800 });
            await page.goto(`${BASE}/dashboard`);

            await expect(page.locator('.nb-prof__hamburger')).not.toBeVisible();
            await expect(page.getByRole('button', { name: 'Log out' })).toBeVisible();
        });
    });
});
