import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

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

const levelSelect = (page: Page) => page.getByLabel('Level');

// The index lists newest first, so a path just created is on the first page;
// its row is found by the name, which each test makes unique.
const row = (page: Page, name: string) => page.locator('table tbody tr', { has: page.getByText(name, { exact: true }) });

// Creates a path through the form, choosing `level` unless it is null, and
// lands back on the index.
async function createPath(page: Page, name: string, level: string | null): Promise<void> {
    await page.goto(`${BASE}/admin/learning-paths/create`);
    await page.getByPlaceholder('e.g. Beginner Bulgarian').fill(name);
    await page.getByPlaceholder('e.g. Bulgarian').fill('bg');

    if (level !== null) {
        await levelSelect(page).selectOption(level);
    }

    await page.getByRole('button', { name: 'Create Path' }).click();
    await expect(page).toHaveURL(`${BASE}/admin/learning-paths`);
}

// Each test leaves nothing behind for the specs that count rows in parallel.
async function deletePath(page: Page, name: string): Promise<void> {
    await page.goto(`${BASE}/admin/learning-paths`);
    page.once('dialog', (dialog) => dialog.accept());
    await row(page, name).getByRole('button', { name: 'Delete' }).click();
    await expect(row(page, name)).toHaveCount(0);
}

const uniqueName = (label: string) => `Level e2e ${label} ${Date.now()}-${Math.floor(Math.random() * 1e6)}`;

test.describe('Admin learning path level', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin user is unavailable in this environment');
    });

    test('the create form offers Not set and every level from A1 to C2, Not set first', async ({ page }) => {
        await page.goto(`${BASE}/admin/learning-paths/create`);

        await expect(levelSelect(page).locator('option:checked')).toHaveText('Not set');
        await expect(levelSelect(page).locator('option')).toHaveText([
            'Not set',
            'A1 Beginner',
            'A2 Elementary',
            'B1 Intermediate',
            'B2 Upper intermediate',
            'C1 Advanced',
            'C2 Proficient',
        ]);
    });

    test('a path created with a level shows it in the list', async ({ page }) => {
        const name = uniqueName('create');

        await createPath(page, name, 'B1');

        await expect(row(page, name).getByTestId('learning-path-level')).toHaveText('B1');
        await deletePath(page, name);
    });

    test('a path created without a level is listed as Not set', async ({ page }) => {
        const name = uniqueName('none');

        await createPath(page, name, null);

        await expect(row(page, name).getByTestId('learning-path-level')).toHaveText('Not set');
        await deletePath(page, name);
    });

    test('editing shows the saved level, changes it, and can clear it', async ({ page }) => {
        const name = uniqueName('edit');
        await createPath(page, name, 'A2');

        await row(page, name).getByRole('link', { name: 'Edit' }).click();
        await expect(levelSelect(page)).toHaveValue('A2');

        await levelSelect(page).selectOption('C2');
        await page.getByRole('button', { name: /^Save/ }).click();
        await expect(page).toHaveURL(`${BASE}/admin/learning-paths`);
        await expect(row(page, name).getByTestId('learning-path-level')).toHaveText('C2');

        await row(page, name).getByRole('link', { name: 'Edit' }).click();
        await expect(levelSelect(page)).toHaveValue('C2');
        await levelSelect(page).selectOption({ label: 'Not set' });
        await page.getByRole('button', { name: /^Save/ }).click();
        await expect(row(page, name).getByTestId('learning-path-level')).toHaveText('Not set');

        await deletePath(page, name);
    });

    test('the list has a Level column', async ({ page }) => {
        await page.goto(`${BASE}/admin/learning-paths`);

        const empty = page.getByText('No learning paths yet.');
        test.skip(await empty.isVisible(), 'no learning paths seeded in this environment');

        await expect(page.getByRole('columnheader', { name: 'Level' })).toBeVisible();
    });
});
