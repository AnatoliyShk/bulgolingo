import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, shared with the other admin specs.
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

// The create form hangs off a lesson and the edit form off an exercise, so
// both ids come from the environment.
const LESSON_ID = process.env.E2E_LESSON_ID ?? '';
const EXERCISE_ID = process.env.E2E_WORD_PAIR_EXERCISE_ID ?? '';

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

// The Type label is not associated with its select, so the select is found
// by one of the type names it always lists.
const typeSelect = (page: Page) =>
    page.locator('select').filter({ has: page.getByRole('option', { name: 'True/False' }) });
const explanation = (page: Page) => page.getByPlaceholder('Explain the correct answer');
const optionInputs = (page: Page) => page.getByPlaceholder(/^Option \d+$/);
const correctCheckbox = (page: Page, n: number) => page.getByTitle(`Mark option ${n} as correct`);

test.describe('Admin exercise form, creating', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin test user is unavailable in this environment');
        test.skip(!LESSON_ID, 'set E2E_LESSON_ID to a lesson that accepts new exercises');

        await page.goto(`${BASE}/admin/lessons/${LESSON_ID}/exercises/create`);
        await expect(typeSelect(page)).toBeVisible();
    });

    test('opens with no type chosen and no clause fields', async ({ page }) => {
        await expect(typeSelect(page)).toHaveValue('');
        await expect(page.getByRole('option', { name: 'Select a type' })).toHaveCount(1);
        await expect(explanation(page)).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Create Exercise' })).toBeVisible();
    });

    // Each type opens from the blank clause the server sends with it, so the
    // fields and their starting answer come from ExerciseType::defaultClause().
    test('true/false opens with an empty sentence and True as the answer', async ({ page }) => {
        await typeSelect(page).selectOption('true_false');

        await expect(page.getByPlaceholder('e.g. The sky is green.')).toHaveValue('');
        await expect(page.locator('select').filter({ has: page.getByRole('option', { name: 'False', exact: true }) })).toHaveValue('true');
        await expect(explanation(page)).toHaveCount(1);
    });

    test('fill in the blank opens with four options and the first marked correct', async ({ page }) => {
        await typeSelect(page).selectOption('fill_in_the_blank');

        await expect(optionInputs(page)).toHaveCount(4);
        await expect(correctCheckbox(page, 1)).toBeChecked();
        await expect(explanation(page)).toHaveCount(1);
    });

    test('ticking another fill in the blank option moves the answer to it', async ({ page }) => {
        await typeSelect(page).selectOption('fill_in_the_blank');
        await correctCheckbox(page, 3).check();

        await expect(correctCheckbox(page, 3)).toBeChecked();
        await expect(correctCheckbox(page, 1)).not.toBeChecked();
    });

    test('image matching opens with the image upload, four options and answer 0', async ({ page }) => {
        await typeSelect(page).selectOption('image_matching');

        await expect(page.locator('.image-upload')).toBeVisible();
        await expect(optionInputs(page)).toHaveCount(4);
        await expect(page.locator('input[type="number"]')).toHaveValue('0');
        await expect(explanation(page)).toHaveCount(1);
    });

    test('switching type starts the new type from its own blank clause', async ({ page }) => {
        await typeSelect(page).selectOption('fill_in_the_blank');
        await page.getByPlaceholder('e.g. The __ is on the table').fill('Аз __ кафе.');
        await explanation(page).fill('Typed before switching.');

        await typeSelect(page).selectOption('true_false');

        await expect(page.getByPlaceholder('e.g. The sky is green.')).toHaveValue('');
        await expect(explanation(page)).toHaveValue('');
    });

    test('cancel leads back to the lesson', async ({ page }) => {
        await expect(page.getByRole('link', { name: 'Cancel' })).toHaveAttribute('href', new RegExp(`/admin/lessons/${LESSON_ID}/edit$`));
    });

    test('the server reports a missing name', async ({ page }) => {
        await typeSelect(page).selectOption('true_false');
        await page.getByPlaceholder('e.g. The sky is green.').fill('Небето е зелено.');
        await page.getByRole('button', { name: 'Create Exercise' }).click();

        await expect(page.getByText('The name field is required.')).toBeVisible();
    });
});

test.describe('Admin exercise form, editing', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin test user is unavailable in this environment');
        test.skip(!EXERCISE_ID, 'set E2E_WORD_PAIR_EXERCISE_ID to a word pair exercise');

        await page.goto(`${BASE}/admin/exercises/${EXERCISE_ID}/edit`);
        await expect(typeSelect(page)).toBeVisible();
    });

    test('opens on the stored exercise with no type placeholder', async ({ page }) => {
        await expect(page.getByPlaceholder('Exercise name')).not.toHaveValue('');
        await expect(typeSelect(page)).toHaveValue('multiple_choice');
        await expect(page.getByRole('option', { name: 'Select a type' })).toHaveCount(0);
        await expect(page.getByPlaceholder('Word', { exact: true }).first()).not.toHaveValue('');
        await expect(explanation(page)).not.toHaveValue('');
        await expect(page.getByRole('button', { name: 'Update Exercise' })).toBeVisible();
    });

    test('switching type drops the stored clause for the new type\'s blank one', async ({ page }) => {
        await typeSelect(page).selectOption('fill_in_the_blank');

        await expect(optionInputs(page)).toHaveCount(4);
        await expect(optionInputs(page).first()).toHaveValue('');
        await expect(explanation(page)).toHaveValue('');
    });
});
