import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded read-only admin visitor account (see database/seeders/AdminVisitorSeeder).
const VISITOR_EMAIL = process.env.E2E_ADMIN_VISITOR_EMAIL ?? 'admin@admin.com';
const VISITOR_PASSWORD = process.env.E2E_ADMIN_VISITOR_PASSWORD ?? 'admin';

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

const loginAsVisitor = (page: Page) => login(page, VISITOR_EMAIL, VISITOR_PASSWORD);

// The catalog meets anyone who has not picked a level with a modal that
// swallows every click on the page behind it, so the prompt is answered
// before the top bar can be used.
async function chooseLevel(page: Page): Promise<void> {
    const prompt = page.getByRole('dialog', { name: 'Choose your level' });
    await prompt.waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});

    if (await prompt.isVisible()) {
        await prompt.getByRole('button').first().click();
        await expect(prompt).toBeHidden();
    }
}

test.describe('Admin visitor role', () => {
    test.describe('when authenticated as an admin visitor', () => {
        test.beforeEach(async ({ page }) => {
            test.skip(!(await loginAsVisitor(page)), 'seeded admin visitor test user is unavailable in this environment');
        });

        // The top bar on the student-facing pages is the visitor's way into the
        // panel, so its Admin link must show for them and not only for full admins.
        test('sees the Admin link in the top bar and it leads to the panel', async ({ page }) => {
            await page.goto(`${BASE}/learning-paths`);
            await chooseLevel(page);

            const adminLink = page.locator('.nb-topbar__link', { hasText: 'Admin' });
            await expect(adminLink).toBeVisible();

            await adminLink.click();
            await page.waitForURL(/\/admin$/);
            await expect(page.getByText('Read-only visitor access')).toBeVisible();
        });

        test('can open the admin panel and sees a read-only notice with the Users section hidden', async ({ page }) => {
            await page.goto(`${BASE}/admin`);
            await expect(page.getByText('Read-only visitor access')).toBeVisible();
            await expect(page.getByRole('heading', { name: 'Users' })).toHaveCount(0);
        });

        test('cannot view the users list', async ({ page }) => {
            const response = await page.goto(`${BASE}/admin/users`);
            expect(response?.status()).toBe(403);
        });

        test('cannot view the messengers list', async ({ page }) => {
            const response = await page.goto(`${BASE}/admin/messengers`);
            expect(response?.status()).toBe(403);
        });

        test('can browse a read-only admin page, such as web vitals', async ({ page }) => {
            const response = await page.goto(`${BASE}/admin/vitals`);
            expect(response?.status()).toBe(200);
        });

        test('cannot create a bot', async ({ page }) => {
            await page.goto(`${BASE}/admin/bots/create`);
            await page.fill('#name', 'Blocked Bot');

            const [response] = await Promise.all([
                page.waitForResponse((res) => res.url().endsWith('/admin/bots') && res.request().method() === 'POST'),
                page.click('button[type="submit"]'),
            ]);

            expect(response.status()).toBe(403);
        });
    });
});
