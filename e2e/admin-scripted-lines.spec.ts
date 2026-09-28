import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, shared with the other admin specs.
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

// The seeded dialogue and its line, exported by e2e:fixture-env.
const DIALOGUE_ID = process.env.E2E_SCRIPTED_DIALOGUE_ID ?? '';
const LINE_ID = process.env.E2E_SCRIPTED_LINE_ID ?? '';

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

const lineText = (page: Page) => page.locator('#line_text');
const option = (page: Page, i: number) => page.locator(`#option_${i}`);
const correct = (page: Page, i: number) => page.locator(`#correct_${i}`);

test.describe('Admin scripted line form, creating', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin test user is unavailable in this environment');
    });

    test('opens blank with three options and the first marked correct', async ({ page }) => {
        await page.goto(`${BASE}/admin/scripted-lines/create`);

        await expect(page.locator('#scripted_dialogue_id')).toHaveValue('');
        await expect(lineText(page)).toHaveValue('');
        await expect(page.locator('.scripted-line-form__option-input')).toHaveCount(3);
        await expect(correct(page, 0)).toBeChecked();
        await expect(page.getByRole('button', { name: 'Create Line' })).toBeVisible();
    });

    test('a dialogue named in the query is preselected', async ({ page }) => {
        test.skip(!DIALOGUE_ID, 'set E2E_SCRIPTED_DIALOGUE_ID to a scripted dialogue');

        await page.goto(`${BASE}/admin/scripted-lines/create?scripted_dialogue_id=${DIALOGUE_ID}`);

        await expect(page.locator('#scripted_dialogue_id')).toHaveValue(DIALOGUE_ID);
    });

    test('choosing another answer moves the highlight to its option', async ({ page }) => {
        await page.goto(`${BASE}/admin/scripted-lines/create`);
        await correct(page, 2).check();

        await expect(option(page, 2)).toHaveClass(/scripted-line-form__option-input--active/);
        await expect(option(page, 0)).toHaveClass(/scripted-line-form__option-input--inactive/);
    });

    test('an empty submission is sent back with its errors', async ({ page }) => {
        await page.goto(`${BASE}/admin/scripted-lines/create`);
        await page.getByRole('button', { name: 'Create Line' }).click();

        await expect(page.getByText('The scripted dialogue id field is required.')).toBeVisible();
        await expect(page.getByText('The line text field is required.')).toBeVisible();
    });

    test('a filled line is created and listed', async ({ page }) => {
        test.skip(!DIALOGUE_ID, 'set E2E_SCRIPTED_DIALOGUE_ID to a scripted dialogue');
        const text = `E2E line ${Date.now()}`;

        await page.goto(`${BASE}/admin/scripted-lines/create?scripted_dialogue_id=${DIALOGUE_ID}`);
        await lineText(page).fill(text);
        await option(page, 0).fill('Да.');
        await option(page, 1).fill('Не.');
        await option(page, 2).fill('Може би.');
        await correct(page, 1).check();
        await page.getByRole('button', { name: 'Create Line' }).click();

        await page.waitForURL((url) => url.pathname === '/admin/scripted-lines');
        await expect(page.getByRole('cell', { name: text })).toBeVisible();
    });

    test('cancel leads back to the list', async ({ page }) => {
        await page.goto(`${BASE}/admin/scripted-lines/create`);

        await expect(page.getByRole('link', { name: 'Cancel' })).toHaveAttribute('href', /\/admin\/scripted-lines$/);
    });
});

test.describe('Admin scripted line form, editing', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin test user is unavailable in this environment');
        test.skip(!LINE_ID || !DIALOGUE_ID, 'set E2E_SCRIPTED_LINE_ID and E2E_SCRIPTED_DIALOGUE_ID to the seeded line');

        await page.goto(`${BASE}/admin/scripted-lines/${LINE_ID}/edit`);
        await expect(lineText(page)).toBeVisible();
    });

    test('opens on the stored line', async ({ page }) => {
        await expect(page.locator('#scripted_dialogue_id')).toHaveValue(DIALOGUE_ID);
        await expect(lineText(page)).not.toHaveValue('');
        for (const i of [0, 1, 2]) {
            await expect(option(page, i)).not.toHaveValue('');
        }
        await expect(page.locator('.scripted-line-form__option-radio:checked')).toHaveCount(1);
        await expect(page.getByRole('button', { name: 'Save' })).toBeVisible();
    });

    // The saved text is read back from a fresh load of the edit page, so the
    // assertion covers what the server stored rather than what the form held.
    test('saving keeps the new text and answer', async ({ page }) => {
        const text = `Добър ден! ${Date.now()}`;

        await lineText(page).fill(text);
        await correct(page, 2).check();
        await page.getByRole('button', { name: 'Save' }).click();
        await page.waitForURL((url) => url.pathname === '/admin/scripted-lines');

        await page.goto(`${BASE}/admin/scripted-lines/${LINE_ID}/edit`);
        await expect(lineText(page)).toHaveValue(text);
        await expect(correct(page, 2)).toBeChecked();
    });
});
