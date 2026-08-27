import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, created for admin-metrics-page verification; reused here.
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

// The create form hangs off a lesson, so which lesson has to come from the environment.
const LESSON_ID = process.env.E2E_LESSON_ID ?? '';

// Mirrors ExerciseType::MIN_WORD_PAIRS.
const MIN_PAIRS = 5;

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

const wordInputs = (page: Page) => page.getByPlaceholder('Word', { exact: true });
const translationInputs = (page: Page) => page.getByPlaceholder('Translation', { exact: true });
const removeButtons = (page: Page) => page.getByRole('button', { name: 'Remove' });
const addPair = (page: Page) => page.getByRole('button', { name: '+ Add pair' });
const submit = (page: Page) => page.getByRole('button', { name: /Create Exercise/ });
const counter = (page: Page) => page.getByText(/\d+ pairs · \d+ words/);

// Fills every visible pair row with a distinct English/Bulgarian word.
async function fillPairs(page: Page): Promise<void> {
    const english = ['hello', 'thank you', 'water', 'bread', 'friend', 'morning'];
    const bulgarian = ['здравей', 'благодаря', 'вода', 'хляб', 'приятел', 'утро'];

    const count = await wordInputs(page).count();
    for (let i = 0; i < count; i++) {
        await wordInputs(page).nth(i).fill(english[i % english.length]);
        await translationInputs(page).nth(i).fill(bulgarian[i % bulgarian.length]);
    }
}

test.describe('Admin word pair exercise form', () => {
    // The Type label is not associated with its select, so the type is chosen
    // by anchoring on the placeholder option instead.
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin test user is unavailable in this environment');
        test.skip(!LESSON_ID, 'set E2E_LESSON_ID to a lesson that accepts new exercises');

        await page.goto(`${BASE}/admin/lessons/${LESSON_ID}/exercises/create`);
        await page
            .locator('select')
            .filter({ has: page.getByRole('option', { name: 'Select a type' }) })
            .selectOption('multiple_choice');
        await expect(wordInputs(page).first()).toBeVisible();
    });

    test('opens with the minimum number of pair rows', async ({ page }) => {
        await expect(wordInputs(page)).toHaveCount(MIN_PAIRS);
        await expect(translationInputs(page)).toHaveCount(MIN_PAIRS);
        await expect(counter(page)).toHaveText(`${MIN_PAIRS} pairs · ${MIN_PAIRS * 2} words`);
    });

    test('states the ten-word requirement', async ({ page }) => {
        await expect(page.getByText(`At least ${MIN_PAIRS} pairs: 10 words, 5 per language.`)).toBeVisible();
    });

    test('remove is disabled while sitting at the minimum', async ({ page }) => {
        for (const button of await removeButtons(page).all()) {
            await expect(button).toBeDisabled();
        }
    });

    test('adding a pair re-enables remove and updates the word count', async ({ page }) => {
        await addPair(page).click();

        await expect(wordInputs(page)).toHaveCount(MIN_PAIRS + 1);
        await expect(counter(page)).toHaveText(`${MIN_PAIRS + 1} pairs · ${(MIN_PAIRS + 1) * 2} words`);
        await expect(removeButtons(page).first()).toBeEnabled();
    });

    // The floor holds by disabling the remaining buttons rather than by
    // refusing the click, so the last assertions check for disabled ones.
    test('removing never drops below the minimum', async ({ page }) => {
        await addPair(page).click();
        await addPair(page).click();
        await expect(wordInputs(page)).toHaveCount(MIN_PAIRS + 2);

        await removeButtons(page).last().click();
        await removeButtons(page).last().click();
        await expect(wordInputs(page)).toHaveCount(MIN_PAIRS);

        await expect(removeButtons(page).last()).toBeDisabled();
        await expect(wordInputs(page)).toHaveCount(MIN_PAIRS);
    });

    test('submit stays available once the minimum rows are filled', async ({ page }) => {
        await page.getByPlaceholder('Exercise name').fill('E2E word pairs');
        await fillPairs(page);
        await page.getByPlaceholder('Explain the correct answer').fill('Match each word to its translation.');

        await expect(submit(page)).toBeEnabled();
    });

    test('the server rejects a duplicated word', async ({ page }) => {
        await page.getByPlaceholder('Exercise name').fill('E2E duplicate words');
        await fillPairs(page);
        await wordInputs(page).last().fill(await wordInputs(page).first().inputValue());
        await page.getByPlaceholder('Explain the correct answer').fill('Match each word to its translation.');

        await submit(page).click();

        await expect(page.getByText('Each word may only appear once in the first column.')).toBeVisible();
    });
});
