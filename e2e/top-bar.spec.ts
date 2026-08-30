import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded credentials (database/seeders/DatabaseSeeder.php); override via env
// when running against an environment with different fixtures.
const EMAIL = process.env.E2E_USER_EMAIL ?? 'test@example.com';
const PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

// Every page that carries the shared bar, split by who can reach it.
const GUEST_PAGES = ['/', '/learning-paths'];
const MEMBER_PAGES = ['/', '/learning-paths', '/dashboard', '/stats'];

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

test.describe('Shared top bar', () => {
    test.describe('for a guest', () => {
        for (const path of GUEST_PAGES) {
            test(`renders the same bar on ${path}`, async ({ page }) => {
                await page.setViewportSize({ width: 1280, height: 800 });
                await page.goto(`${BASE}${path}`);

                await expect(page.locator('.nb-topbar')).toBeVisible();
                await expect(page.locator('.nb-topbar__logo')).toHaveAttribute('href', '/');
                await expect(page.locator('.nb-toggle')).toBeVisible();

                await expect(page.getByRole('link', { name: 'Learning paths' })).toBeVisible();
                await expect(page.getByRole('link', { name: 'Login' })).toBeVisible();
                await expect(page.getByRole('link', { name: 'Register' })).toBeVisible();

                // Signed-out visitors are never offered the member destinations.
                await expect(page.locator('.nb-topbar').getByRole('link', { name: 'Dashboard' })).toHaveCount(0);
                await expect(page.locator('.nb-topbar').getByRole('button', { name: 'Log out' })).toHaveCount(0);
            });
        }

        test('marks the page you are on', async ({ page }) => {
            await page.setViewportSize({ width: 1280, height: 800 });
            await page.goto(`${BASE}/learning-paths`);

            await expect(page.getByRole('link', { name: 'Learning paths' }))
                .toHaveAttribute('aria-current', 'page');
            await expect(page.getByRole('link', { name: 'Login' }))
                .not.toHaveAttribute('aria-current', 'page');
        });

        test('collapses behind a hamburger on a narrow viewport', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 800 });
            await page.goto(`${BASE}/`);

            const hamburger = page.locator('.nb-topbar__hamburger');
            const links = page.locator('.nb-topbar__links');

            await expect(hamburger).toBeVisible();
            await expect(page.locator('.nb-toggle')).toBeVisible();
            await expect(links).not.toBeVisible();

            await hamburger.click();
            await expect(links).toBeVisible();
            await expect(page.getByRole('link', { name: 'Register' })).toBeVisible();

            await hamburger.click();
            await expect(links).not.toBeVisible();
        });

        test('shows the links inline on a wide viewport', async ({ page }) => {
            await page.setViewportSize({ width: 1280, height: 800 });
            await page.goto(`${BASE}/`);

            await expect(page.locator('.nb-topbar__hamburger')).not.toBeVisible();
            await expect(page.locator('.nb-topbar__links')).toBeVisible();
        });
    });

    test.describe('for a member', () => {
        test.beforeEach(async ({ page }) => {
            test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');
        });

        for (const path of MEMBER_PAGES) {
            test(`renders the same bar on ${path}`, async ({ page }) => {
                await page.setViewportSize({ width: 1280, height: 800 });
                await page.goto(`${BASE}${path}`);

                await expect(page.locator('.nb-topbar')).toBeVisible();
                await expect(page.locator('.nb-toggle')).toBeVisible();

                await expect(page.getByRole('link', { name: 'Learning paths' })).toBeVisible();
                await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
                await expect(page.getByRole('link', { name: 'Stats' })).toBeVisible();
                await expect(page.getByRole('button', { name: 'Log out' })).toBeVisible();

                // Signed-in visitors are never offered the signed-out destinations.
                await expect(page.locator('.nb-topbar').getByRole('link', { name: 'Login' })).toHaveCount(0);
                await expect(page.locator('.nb-topbar').getByRole('link', { name: 'Register' })).toHaveCount(0);
            });
        }

        test('marks the page you are on', async ({ page }) => {
            await page.setViewportSize({ width: 1280, height: 800 });
            await page.goto(`${BASE}/stats`);

            await expect(page.getByRole('link', { name: 'Stats' }))
                .toHaveAttribute('aria-current', 'page');
            await expect(page.getByRole('link', { name: 'Dashboard' }))
                .not.toHaveAttribute('aria-current', 'page');
        });

        test('logs the member out from any page carrying the bar', async ({ page }) => {
            await page.setViewportSize({ width: 1280, height: 800 });
            await page.goto(`${BASE}/stats`);

            await page.getByRole('button', { name: 'Log out' }).click();
            await page.waitForURL(`${BASE}/`, { timeout: 15000 }).catch(() => {});

            await expect(page.getByRole('link', { name: 'Login' })).toBeVisible();
        });

        test('collapses behind a hamburger on a narrow viewport', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 800 });
            await page.goto(`${BASE}/dashboard`);

            const hamburger = page.locator('.nb-topbar__hamburger');
            const links = page.locator('.nb-topbar__links');

            await expect(hamburger).toBeVisible();
            await expect(links).not.toBeVisible();

            await hamburger.click();
            await expect(links).toBeVisible();
            await expect(page.getByRole('button', { name: 'Log out' })).toBeVisible();

            await hamburger.click();
            await expect(links).not.toBeVisible();
        });
    });
});
