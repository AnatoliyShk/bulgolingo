import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded credentials (database/seeders/DatabaseSeeder.php); override via env
// when running against an environment with different fixtures.
const EMAIL = process.env.E2E_USER_EMAIL ?? 'test@example.com';
const PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

// The seeded user is verified (UserFactory defaults email_verified_at to now),
// so the unverified branch needs a second account supplied by the environment.
const UNVERIFIED_EMAIL = process.env.E2E_UNVERIFIED_USER_EMAIL ?? '';
const UNVERIFIED_PASSWORD = process.env.E2E_UNVERIFIED_USER_PASSWORD ?? PASSWORD;

// Logs in through the UI; returns false when the seeded user is unavailable
// so callers can skip instead of failing on fixture-less environments.
async function login(page: Page, email = EMAIL, password = PASSWORD): Promise<boolean> {
    await page.goto(`${BASE}/login`);
    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('login'), { timeout: 15000 }).catch(() => {});
    return !page.url().includes('/login');
}

test.describe('Profile / dashboard page', () => {
    test.describe('when authenticated', () => {
        test.beforeEach(async ({ page }) => {
            test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');
        });

        test('avatar carries a verified badge for a confirmed email', async ({ page }) => {
            await page.goto(`${BASE}/dashboard`);

            const badge = page.locator('.nb-prof__avatar .nb-prof__avatar-badge');

            await expect(badge).toBeVisible();
            await expect(badge).toHaveClass(/nb-prof__avatar-badge--verified/);
            await expect(badge).not.toHaveClass(/nb-prof__avatar-badge--unverified/);
            await expect(badge).toHaveAttribute('title', 'Email verified');
            await expect(page.getByRole('img', { name: 'Email verified' })).toBeVisible();
            await expect(badge.locator('svg')).toBeVisible();
        });

        test('verified badge survives a theme switch', async ({ page }) => {
            await page.goto(`${BASE}/dashboard`);

            const badge = page.locator('.nb-prof__avatar-badge');
            await expect(badge).toBeVisible();

            await page.locator('.nb-toggle').click();
            await expect(badge).toBeVisible();
            await expect(badge).toHaveClass(/nb-prof__avatar-badge--verified/);
        });
    });

    test.describe('when the email is unverified', () => {
        test.beforeEach(async ({ page }) => {
            test.skip(
                !UNVERIFIED_EMAIL,
                'set E2E_UNVERIFIED_USER_EMAIL to an account with a null email_verified_at',
            );
            test.skip(
                !(await login(page, UNVERIFIED_EMAIL, UNVERIFIED_PASSWORD)),
                'the unverified test user is unavailable in this environment',
            );
        });

        test('avatar carries an unverified badge', async ({ page }) => {
            await page.goto(`${BASE}/dashboard`);

            const badge = page.locator('.nb-prof__avatar .nb-prof__avatar-badge');

            await expect(badge).toBeVisible();
            await expect(badge).toHaveClass(/nb-prof__avatar-badge--unverified/);
            await expect(badge).not.toHaveClass(/nb-prof__avatar-badge--verified/);
            await expect(badge).toHaveAttribute('title', 'Email not verified');
            await expect(page.getByRole('img', { name: 'Email not verified' })).toBeVisible();
            await expect(badge.locator('svg')).toBeVisible();
        });
    });
});
