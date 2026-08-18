import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, created for admin-metrics-page verification; reused here.
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

// Seeded non-admin credentials (see database/seeders; used for the Stats page e2e specs too).
const USER_EMAIL = process.env.E2E_USER_EMAIL ?? 'e2e-stats@example.com';
const USER_PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

// Logs in through the UI; returns false when the seeded user is unavailable
// so callers can skip instead of failing on fixture-less environments.
async function login(page: Page, email: string, password: string): Promise<boolean> {
    await page.goto(`${BASE}/login`);
    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('login'), { timeout: 15000 }).catch(() => {});
    return !page.url().includes('/login');
}

const loginAsAdmin = (page: Page) => login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
const loginAsUser = (page: Page) => login(page, USER_EMAIL, USER_PASSWORD);

test.describe('Admin log viewer (opcodesio/log-viewer)', () => {
    test('redirects guests to the login page', async ({ page }) => {
        await page.goto(`${BASE}/admin/logs`);
        await expect(page).toHaveURL(`${BASE}/login`);
    });

    test('is forbidden for a non-admin user', async ({ page }) => {
        test.skip(!(await loginAsUser(page)), 'seeded non-admin test user is unavailable in this environment');

        const response = await page.goto(`${BASE}/admin/logs`);
        expect(response?.status()).toBe(403);
    });

    test.describe('when authenticated as an admin', () => {
        test.beforeEach(async ({ page }) => {
            test.skip(!(await loginAsAdmin(page)), 'seeded admin test user is unavailable in this environment');
        });

        test('loads the log viewer', async ({ page }) => {
            const response = await page.goto(`${BASE}/admin/logs`);
            expect(response?.status()).toBe(200);
            await expect(page.getByText('Log Viewer')).toBeVisible();
        });

        test('offers a severity filter with Error, Warning and Info options', async ({ page }) => {
            await page.goto(`${BASE}/admin/logs`);
            await page.getByText('laravel.log').click();

            const filterToggle = page.getByText(/entries in/);
            await expect(filterToggle).toBeVisible({ timeout: 15000 });
            await filterToggle.click();

            await expect(page.getByRole('menuitem', { name: 'Error' })).toBeVisible();
            await expect(page.getByRole('menuitem', { name: 'Warning' })).toBeVisible();
            await expect(page.getByRole('menuitem', { name: 'Info' })).toBeVisible();
        });

        test('is reachable from the admin panel nav card', async ({ page }) => {
            await page.goto(`${BASE}/admin`);
            await page.getByRole('link', { name: /^Logs/ }).click();
            await expect(page).toHaveURL(`${BASE}/admin/logs`);
        });
    });
});
