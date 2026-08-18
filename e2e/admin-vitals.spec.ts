import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, created for admin-metrics-page verification (reused here).
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

// Seeded non-admin credentials (see database/seeders; used for the Stats page e2e specs too).
const USER_EMAIL = process.env.E2E_USER_EMAIL ?? 'e2e-stats@example.com';
const USER_PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

const METRIC_NAMES = ['LCP', 'INP', 'CLS', 'TTFB'];

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

test.describe('Admin web vitals page', () => {
    test('redirects guests to the login page', async ({ page }) => {
        await page.goto(`${BASE}/admin/vitals`);
        await expect(page).toHaveURL(`${BASE}/login`);
    });

    test.describe('when authenticated as a non-admin', () => {
        test('is forbidden', async ({ page }) => {
            test.skip(!(await loginAsUser(page)), 'seeded non-admin test user is unavailable in this environment');

            const response = await page.goto(`${BASE}/admin/vitals`);
            expect(response?.status()).toBe(403);
        });
    });

    test.describe('when authenticated as an admin', () => {
        test.beforeEach(async ({ page }) => {
            test.skip(!(await loginAsAdmin(page)), 'seeded admin test user is unavailable in this environment');
            await page.goto(`${BASE}/admin/vitals`);
        });

        test('renders a panel for each core web vital', async ({ page }) => {
            const panels = page.locator('.admin-vitals__panel');
            await expect(panels).toHaveCount(4);

            for (const name of METRIC_NAMES) {
                await expect(page.locator('.admin-vitals__panel-title', { hasText: name })).toBeVisible();
            }
        });

        test('shows a p75 stat with rating breakdown, or an empty state, per panel', async ({ page }) => {
            const panels = page.locator('.admin-vitals__panel');
            const count = await panels.count();

            for (let i = 0; i < count; i++) {
                const panel = panels.nth(i);
                const hasStat = (await panel.locator('.admin-vitals__stat-value').count()) > 0;
                const hasEmptyState = (await panel.locator('.admin-vitals__empty').count()) > 0;
                expect(hasStat || hasEmptyState).toBe(true);

                if (hasStat) {
                    await expect(panel.locator('.admin-vitals__stat-value')).toHaveText(/^([\d.]+ms|[\d.]+|—)$/);
                    await expect(panel.locator('.admin-vitals__legend')).toContainText('Good:');
                    await expect(panel.locator('.admin-vitals__legend')).toContainText('Needs improvement:');
                    await expect(panel.locator('.admin-vitals__legend')).toContainText('Poor:');
                }
            }
        });

        test('breadcrumb links back to the admin panel', async ({ page }) => {
            await expect(page.getByRole('link', { name: 'Admin' })).toHaveAttribute('href', `${BASE}/admin`);
            await expect(page.getByText('Web Vitals')).toBeVisible();
        });

        test('is reachable from the admin panel nav card', async ({ page }) => {
            await page.goto(`${BASE}/admin`);
            await page.getByRole('link', { name: /Web Vitals/ }).click();
            await expect(page).toHaveURL(`${BASE}/admin/vitals`);
        });
    });
});
