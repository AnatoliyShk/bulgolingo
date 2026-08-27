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

// YYYY-MM-DD in local time, matching the keys the page compares against.
function isoDay(offsetDays = 0): string {
    const date = new Date();
    date.setDate(date.getDate() + offsetDays);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

const rows = (page: Page) => page.locator('table tbody tr');
const typeSelect = (page: Page) => page.getByLabel('Type', { exact: true });
const lessonSelect = (page: Page) => page.getByLabel('Lesson', { exact: true });
const fromInput = (page: Page) => page.getByLabel('Created from', { exact: true });
const toInput = (page: Page) => page.getByLabel('Created to', { exact: true });
const counter = (page: Page) => page.getByText(/^\s*\d+ \/ \d+ exercises\s*$/);
const emptyMessage = (page: Page) => page.getByText('No exercises match the selected filters.');

test.describe('Admin exercises index filters', () => {
    test('redirects guests to the login page', async ({ page }) => {
        await page.goto(`${BASE}/admin/exercises`);
        await expect(page).toHaveURL(`${BASE}/login`);
    });

    test('is forbidden for a non-admin user', async ({ page }) => {
        test.skip(!(await loginAsUser(page)), 'seeded non-admin test user is unavailable in this environment');

        const response = await page.goto(`${BASE}/admin/exercises`);
        expect(response?.status()).toBe(403);
    });

    test.describe('when authenticated as an admin', () => {
        // Inertia renders client-side, so the filter bar has to appear before
        // anything counts rows.
        test.beforeEach(async ({ page }) => {
            test.skip(!(await loginAsAdmin(page)), 'seeded admin test user is unavailable in this environment');
            await page.goto(`${BASE}/admin/exercises`);
            await expect(counter(page)).toBeVisible();
            test.skip(await rows(page).count() === 0, 'no exercises exist in this environment to filter');
        });

        test('exposes lesson, type and created-date controls', async ({ page }) => {
            await expect(lessonSelect(page)).toBeVisible();
            await expect(typeSelect(page)).toBeVisible();
            await expect(fromInput(page)).toBeVisible();
            await expect(toInput(page)).toBeVisible();
            await expect(fromInput(page)).toHaveAttribute('type', 'date');
            await expect(toInput(page)).toHaveAttribute('type', 'date');
            await expect(typeSelect(page)).toHaveValue('');
            await expect(counter(page)).toBeVisible();
        });

        test('type filter keeps only rows of the chosen type', async ({ page }) => {
            const total = await rows(page).count();
            const chosenType = (await rows(page).first().locator('td').nth(1).innerText()).trim();

            await typeSelect(page).selectOption({ label: chosenType });

            const cells = rows(page).locator('td:nth-child(2)');
            await expect(cells).not.toHaveCount(0);
            for (const text of await cells.allInnerTexts()) {
                expect(text.trim()).toBe(chosenType);
            }

            const matched = await rows(page).count();
            expect(matched).toBeLessThanOrEqual(total);
            await expect(counter(page)).toHaveText(new RegExp(`${matched} / ${total} exercises`));
        });

        test('a future start date filters every exercise out', async ({ page }) => {
            await fromInput(page).fill(isoDay(1));

            await expect(rows(page)).toHaveCount(0);
            await expect(emptyMessage(page)).toBeVisible();
            await expect(counter(page)).toHaveText(/^0 \//);
        });

        test('a past end date filters every exercise out', async ({ page }) => {
            await toInput(page).fill('2000-01-01');

            await expect(rows(page)).toHaveCount(0);
            await expect(emptyMessage(page)).toBeVisible();
        });

        test('a range covering today keeps the exercises created today', async ({ page }) => {
            const total = await rows(page).count();

            await fromInput(page).fill(isoDay(-3650));
            await toInput(page).fill(isoDay());

            await expect(rows(page)).toHaveCount(total);
            await expect(emptyMessage(page)).toHaveCount(0);
        });

        test('the date inputs bound each other', async ({ page }) => {
            await fromInput(page).fill(isoDay(-30));
            await expect(toInput(page)).toHaveAttribute('min', isoDay(-30));

            await toInput(page).fill(isoDay());
            await expect(fromInput(page)).toHaveAttribute('max', isoDay());
        });

        test('clear resets type and date filters together', async ({ page }) => {
            const total = await rows(page).count();
            const chosenType = (await rows(page).first().locator('td').nth(1).innerText()).trim();
            const clear = page.getByRole('button', { name: 'Clear' });

            await expect(clear).toHaveCount(0);

            await typeSelect(page).selectOption({ label: chosenType });
            await fromInput(page).fill(isoDay(-30));
            await toInput(page).fill(isoDay());
            await expect(clear).toBeVisible();

            await clear.click();

            await expect(typeSelect(page)).toHaveValue('');
            await expect(lessonSelect(page)).toHaveValue('');
            await expect(fromInput(page)).toHaveValue('');
            await expect(toInput(page)).toHaveValue('');
            await expect(rows(page)).toHaveCount(total);
            await expect(clear).toHaveCount(0);
        });

        test('type and lesson filters combine', async ({ page }) => {
            const firstRow = rows(page).first();
            const chosenType = (await firstRow.locator('td').nth(1).innerText()).trim();
            const lessonLink = firstRow.locator('td').nth(2).locator('a');

            test.skip(await lessonLink.count() === 0, 'the first exercise has no lesson to filter on');
            const chosenLesson = (await lessonLink.innerText()).trim();

            await typeSelect(page).selectOption({ label: chosenType });
            await lessonSelect(page).selectOption({ label: chosenLesson });

            await expect(rows(page)).not.toHaveCount(0);
            for (const text of await rows(page).locator('td:nth-child(2)').allInnerTexts()) {
                expect(text.trim()).toBe(chosenType);
            }
        });
    });
});
