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

test.describe('Stats page', () => {
    test('redirects guests to the login page', async ({ page }) => {
        await page.goto(`${BASE}/stats`);
        await expect(page).toHaveURL(`${BASE}/login`);
    });

    test.describe('when authenticated', () => {
        test.beforeEach(async ({ page }) => {
            test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');
            await page.goto(`${BASE}/stats`);
        });

        test('renders the neo-brutalist shell with heading and badge', async ({ page }) => {
            await expect(page.locator('.nb-stats')).toBeVisible();
            await expect(page.getByRole('heading', { name: 'Your stats' })).toBeVisible();
            await expect(page.locator('.nb-stats__head .nb-stats__badge')).toHaveText('Статистика');
        });

        test('shows the three KPI cards with numeric values', async ({ page }) => {
            const kpis = page.locator('.nb-stats__kpi');
            await expect(kpis).toHaveCount(3);

            for (const value of await kpis.locator('.nb-stats__kpi-value').all()) {
                await expect(value).toHaveText(/^\d+$/);
            }

            await expect(kpis.nth(0)).toContainText('Упражнения');
            await expect(kpis.nth(1)).toContainText('Уроци');
            await expect(kpis.nth(2)).toContainText('Пътища');
        });

        test('shows the words and activity sections with a chart or empty state', async ({ page }) => {
            const sections = page.locator('.nb-stats__section');
            await expect(sections).toHaveCount(2);

            await expect(page.getByRole('heading', { name: "Words you've learned" })).toBeVisible();
            await expect(page.locator('.nb-stats__section-count')).toHaveText(/^\d+ unique$/);
            await expect(page.getByRole('heading', { name: 'Activity by exercise type' })).toBeVisible();

            for (const section of await sections.all()) {
                const panel = section.locator('.nb-stats__panel');
                await expect(panel).toBeVisible();

                const hasChart = (await panel.locator('svg').count()) > 0;
                const hasEmptyState = (await panel.locator('.nb-stats__empty').count()) > 0;
                expect(hasChart || hasEmptyState).toBe(true);
            }
        });

        test('theme toggle switches between dark and light', async ({ page }) => {
            const shell = page.locator('.nb-stats');
            const toggle = page.locator('.nb-stats__toggle');

            const startedDark = await shell.evaluate((el) => el.classList.contains('dark'));

            await toggle.click();
            await expect(shell).toHaveClass(startedDark ? /light/ : /dark/);

            await toggle.click();
            await expect(shell).toHaveClass(startedDark ? /dark/ : /light/);
        });

        test('bar links back to the dashboard', async ({ page }) => {
            await expect(page.locator('.nb-stats__logo')).toHaveAttribute('href', '/dashboard');
            await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
        });

        test('mobile viewport collapses nav behind a hamburger toggle', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 800 });
            await page.reload();

            const hamburger = page.locator('.nb-stats__hamburger');
            const toggle = page.locator('.nb-stats__toggle');
            const links = page.locator('.nb-stats__bar-links');

            await expect(hamburger).toBeVisible();
            await expect(toggle).toBeVisible();
            await expect(links).not.toBeVisible();

            await hamburger.click();
            await expect(links).toBeVisible();
            await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();

            await hamburger.click();
            await expect(links).not.toBeVisible();
        });

        test('desktop viewport shows nav links inline without a hamburger', async ({ page }) => {
            await page.setViewportSize({ width: 1280, height: 800 });
            await page.reload();

            await expect(page.locator('.nb-stats__hamburger')).not.toBeVisible();
            await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
        });
    });
});
