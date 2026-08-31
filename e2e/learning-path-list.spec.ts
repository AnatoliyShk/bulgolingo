import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded credentials (database/seeders/DatabaseSeeder.php); override via env
// when running against an environment with different fixtures.
const EMAIL = process.env.E2E_USER_EMAIL ?? 'test@example.com';
const PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

async function login(page: Page): Promise<boolean> {
    await page.goto(`${BASE}/login`);
    await page.fill('#email', EMAIL);
    await page.fill('#password', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('login'), { timeout: 15000 }).catch(() => {});
    return !page.url().includes('/login');
}

// The enrolled and finished pages render the same LearningPath/List.vue
// template with different data, so both are exercised the same way here.
test.describe('Enrolled / finished learning path lists', () => {
    test.describe('when authenticated', () => {
        test.beforeEach(async ({ page }) => {
            test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');
        });

        test('enrolled page shows its title and, absent any enrollment, the empty state', async ({ page }) => {
            await page.goto(`${BASE}/learning-paths/enrolled`);

            await expect(page.getByRole('heading', { name: 'Enrolled learning paths' })).toBeVisible();

            const cards = page.locator('.nb-path-list__path');
            if ((await cards.count()) === 0) {
                await expect(page.locator('.nb-path-list__empty')).toBeVisible();
                await expect(page.getByRole('link', { name: /Browse learning paths/ })).toBeVisible();
            }
        });

        test('finished page shows its title and, absent a finished path, the empty state', async ({ page }) => {
            await page.goto(`${BASE}/learning-paths/finished`);

            await expect(page.getByRole('heading', { name: 'Finished learning paths' })).toBeVisible();

            const cards = page.locator('.nb-path-list__path');
            if ((await cards.count()) === 0) {
                await expect(page.locator('.nb-path-list__empty')).toBeVisible();
            }
        });

        test('dashboard links to both lists when an active path is shown', async ({ page }) => {
            await page.goto(`${BASE}/dashboard`);

            const enrolledLink = page.getByRole('link', { name: /All enrolled/ });
            test.skip(!(await enrolledLink.isVisible().catch(() => false)), 'no active learning path on the dashboard in this environment');

            await enrolledLink.click();
            await expect(page).toHaveURL(`${BASE}/learning-paths/enrolled`);

            await page.goto(`${BASE}/dashboard`);
            await page.getByRole('link', { name: /All finished/ }).click();
            await expect(page).toHaveURL(`${BASE}/learning-paths/finished`);
        });
    });

    test('guests are redirected to login', async ({ page }) => {
        await page.goto(`${BASE}/learning-paths/enrolled`);
        await expect(page).toHaveURL(/\/login/);

        await page.goto(`${BASE}/learning-paths/finished`);
        await expect(page).toHaveURL(/\/login/);
    });
});
