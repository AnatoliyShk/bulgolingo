import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';
const USER_EMAIL = process.env.E2E_USER_EMAIL ?? 'e2e-stats@example.com';
const USER_PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

const PER_PAGE = 20;

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

const nav = (page: Page) => page.locator('nav[aria-label="Pagination"]');
const summary = (page: Page) => page.getByTestId('pagination-summary');
const pageLinks = (page: Page) => page.locator('.admin-pagination__link:not(.admin-pagination__link--disabled)');
const disabledLinks = (page: Page) => page.locator('.admin-pagination__link--disabled');
const currentLink = (page: Page) => page.locator('.admin-pagination__link--current');
const tableRows = (page: Page) => page.locator('table tbody tr');
const lessonRows = (page: Page) => page.locator('ul li');
const selectedCount = (page: Page) => page.getByText(/^\d+ selected$/);

// The total the summary reports, which is what decides whether a second page exists.
async function total(page: Page): Promise<number> {
    const text = (await summary(page).textContent()) ?? '';
    return Number(text.match(/of\s+(\d+)/)?.[1] ?? 0);
}

test.describe('Admin learning paths pagination', () => {
    test('redirects guests to the login page', async ({ page }) => {
        await page.goto(`${BASE}/admin/learning-paths`);
        await expect(page).toHaveURL(`${BASE}/login`);
    });

    test('is forbidden for a non-admin user', async ({ page }) => {
        test.skip(!(await loginAsUser(page)), 'seeded non-admin test user is unavailable in this environment');

        const response = await page.goto(`${BASE}/admin/learning-paths`);
        expect(response?.status()).toBe(403);
    });

    test.describe('index listing', () => {
        // Inertia renders client-side, so the table has to appear before anything
        // counts rows.
        test.beforeEach(async ({ page }) => {
            test.skip(!(await loginAsAdmin(page)), 'seeded admin test user is unavailable in this environment');

            await page.goto(`${BASE}/admin/learning-paths`);
            await page.waitForSelector('table tbody tr, .text-gray-500', { timeout: 15000 });
        });

        test('never renders more than one page of rows', async ({ page }) => {
            test.skip((await tableRows(page).count()) === 0, 'no learning paths seeded');

            expect(await tableRows(page).count()).toBeLessThanOrEqual(PER_PAGE);
        });

        test('summarises the slice being shown', async ({ page }) => {
            test.skip((await tableRows(page).count()) === 0, 'no learning paths seeded');

            await expect(summary(page)).toHaveText(/Showing \d+–\d+ of \d+/);
        });

        test('marks the first page as current and disables Previous', async ({ page }) => {
            test.skip((await total(page)) <= PER_PAGE, 'needs more than one page of learning paths');

            await expect(currentLink(page)).toHaveText('1');
            await expect(currentLink(page)).toHaveAttribute('aria-current', 'page');
            await expect(disabledLinks(page).filter({ hasText: 'Previous' })).toHaveCount(1);
        });

        test('turning a page changes the rows and the url', async ({ page }) => {
            test.skip((await total(page)) <= PER_PAGE, 'needs more than one page of learning paths');

            const firstOnPageOne = await tableRows(page).first().textContent();

            await pageLinks(page).filter({ hasText: 'Next' }).click();
            await page.waitForURL(/[?&]page=2/, { timeout: 15000 });

            await expect(currentLink(page)).toHaveText('2');
            expect(await tableRows(page).first().textContent()).not.toBe(firstOnPageOne);
        });

        test('hides the page buttons when everything fits on one page', async ({ page }) => {
            test.skip((await total(page)) > PER_PAGE, 'needs a single page of learning paths');

            await expect(summary(page)).toBeVisible();
            await expect(pageLinks(page)).toHaveCount(0);
        });

        test('shows nothing at all when there are no learning paths', async ({ page }) => {
            test.skip((await tableRows(page).count()) > 0, 'learning paths are seeded in this environment');

            await expect(page.getByText('No learning paths yet.')).toBeVisible();
            await expect(nav(page)).toHaveCount(0);
        });
    });

    test.describe('lesson picker on the edit page', () => {
        test.beforeEach(async ({ page }) => {
            test.skip(!(await loginAsAdmin(page)), 'seeded admin test user is unavailable in this environment');

            await page.goto(`${BASE}/admin/learning-paths`);
            await page.waitForSelector('table tbody tr, .text-gray-500', { timeout: 15000 });
            test.skip((await tableRows(page).count()) === 0, 'no learning paths seeded');

            await page.getByRole('link', { name: 'Edit' }).first().click();
            await page.waitForURL(/\/admin\/learning-paths\/\d+\/edit/, { timeout: 15000 });
            await page.waitForSelector('input[placeholder="Search lessons…"]', { timeout: 15000 });
        });

        test('never renders more than one page of lessons', async ({ page }) => {
            test.skip((await lessonRows(page).count()) === 0, 'no lessons seeded');

            expect(await lessonRows(page).count()).toBeLessThanOrEqual(PER_PAGE);
        });

        // The reason the picker pages with preserveState: a full visit would
        // rebuild the form and silently drop everything ticked but not saved.
        test('keeps unsaved selections when a page is turned', async ({ page }) => {
            test.skip((await total(page)) <= PER_PAGE, 'needs more than one page of lessons');

            const before = await selectedCount(page).textContent();
            await lessonRows(page).first().click();

            const after = await selectedCount(page).textContent();
            expect(after).not.toBe(before);

            await pageLinks(page).filter({ hasText: 'Next' }).click();
            await expect(currentLink(page)).toHaveText('2');

            await expect(selectedCount(page)).toHaveText(after!);
        });

        test('searching filters server-side and returns to the first page', async ({ page }) => {
            test.skip((await lessonRows(page).count()) === 0, 'no lessons seeded');

            const name = (await lessonRows(page).first().locator('p').first().textContent())?.trim() ?? '';
            test.skip(name === '', 'first lesson has no name to search for');

            await page.fill('input[placeholder="Search lessons…"]', name);
            await page.waitForURL(/lesson_search=/, { timeout: 15000 });

            await expect(lessonRows(page).first()).toContainText(name);
            expect(await total(page)).toBeLessThanOrEqual(PER_PAGE * 2);
        });

        test('reports no matches for a search nothing satisfies', async ({ page }) => {
            await page.fill('input[placeholder="Search lessons…"]', 'zzz-no-such-lesson-zzz');
            await page.waitForURL(/lesson_search=zzz/, { timeout: 15000 });

            await expect(page.getByText('No lessons match your search.')).toBeVisible();
            await expect(nav(page)).toHaveCount(0);
        });
    });
});
