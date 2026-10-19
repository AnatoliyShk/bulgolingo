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

// The two cells of a pair are labelled with the languages the exercise names,
// English and Bulgarian by default: the first holds the translation and the
// second the word being taught.
const translationInputs = (page: Page) => page.getByPlaceholder('English', { exact: true });
const termInputs = (page: Page) => page.getByPlaceholder('Bulgarian', { exact: true });
const languageSelect = (page: Page) => page.getByTestId('exercise-language');
const translationLanguageSelect = (page: Page) => page.getByTestId('exercise-translation-language');
const removeButtons = (page: Page) => page.getByRole('button', { name: 'Remove' });
const addPair = (page: Page) => page.getByRole('button', { name: '+ Add pair' });
const submit = (page: Page) => page.getByRole('button', { name: /Create Exercise/ });
const counter = (page: Page) => page.getByText(/\d+ pairs · \d+ words/);
const shuffle = (page: Page) => page.getByRole('button', { name: 'Shuffle' });
const preview = (page: Page) => page.getByTestId('student-order');
const previewColumns = (page: Page) => preview(page).locator('ol');

// Fills every visible pair row with a distinct English translation and
// Bulgarian word.
async function fillPairs(page: Page): Promise<void> {
    const english = ['hello', 'thank you', 'water', 'bread', 'friend', 'morning'];
    const bulgarian = ['здравей', 'благодаря', 'вода', 'хляб', 'приятел', 'утро'];

    const count = await translationInputs(page).count();
    for (let i = 0; i < count; i++) {
        await translationInputs(page).nth(i).fill(english[i % english.length]);
        await termInputs(page).nth(i).fill(bulgarian[i % bulgarian.length]);
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
        await expect(translationInputs(page).first()).toBeVisible();
    });

    test('opens with the minimum number of pair rows', async ({ page }) => {
        await expect(translationInputs(page)).toHaveCount(MIN_PAIRS);
        await expect(termInputs(page)).toHaveCount(MIN_PAIRS);
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

        await expect(translationInputs(page)).toHaveCount(MIN_PAIRS + 1);
        await expect(counter(page)).toHaveText(`${MIN_PAIRS + 1} pairs · ${(MIN_PAIRS + 1) * 2} words`);
        await expect(removeButtons(page).first()).toBeEnabled();
    });

    // The floor holds by disabling the remaining buttons rather than by
    // refusing the click, so the last assertions check for disabled ones.
    test('removing never drops below the minimum', async ({ page }) => {
        await addPair(page).click();
        await addPair(page).click();
        await expect(translationInputs(page)).toHaveCount(MIN_PAIRS + 2);

        await removeButtons(page).last().click();
        await removeButtons(page).last().click();
        await expect(translationInputs(page)).toHaveCount(MIN_PAIRS);

        await expect(removeButtons(page).last()).toBeDisabled();
        await expect(translationInputs(page)).toHaveCount(MIN_PAIRS);
    });

    test('submit stays available once the minimum rows are filled', async ({ page }) => {
        await page.getByPlaceholder('Exercise name').fill('E2E word pairs');
        await fillPairs(page);
        await page.getByPlaceholder('Explain the correct answer').fill('Match each word to its translation.');

        await expect(submit(page)).toBeEnabled();
    });

    test('no student order is shown until the columns are shuffled', async ({ page }) => {
        await expect(shuffle(page)).toBeVisible();
        await expect(page.getByText('Order the student sees')).toHaveCount(0);
    });

    // Shuffling stores a permutation of the pairs, so the preview must hold
    // every word exactly once no matter which order it lands in.
    test('shuffling reveals the order the student will see', async ({ page }) => {
        await fillPairs(page);
        await shuffle(page).click();

        await expect(page.getByText('Order the student sees')).toBeVisible();
        await expect(previewColumns(page)).toHaveCount(2);

        const words = await translationInputs(page).evaluateAll((inputs) =>
            (inputs as HTMLInputElement[]).map((input) => input.value).sort(),
        );
        const previewed = await previewColumns(page).first().locator('li').allInnerTexts();

        expect(previewed).toHaveLength(MIN_PAIRS);
        expect(previewed.map((word) => word.trim()).sort()).toEqual(words);
    });

    test('a pair added after shuffling joins the stored order', async ({ page }) => {
        await fillPairs(page);
        await shuffle(page).click();
        await addPair(page).click();

        await expect(previewColumns(page).first().locator('li')).toHaveCount(MIN_PAIRS + 1);
        await expect(previewColumns(page).last().locator('li')).toHaveCount(MIN_PAIRS + 1);
    });

    test('a pair removed after shuffling leaves the stored order', async ({ page }) => {
        await fillPairs(page);
        await shuffle(page).click();
        await addPair(page).click();
        await removeButtons(page).last().click();

        await expect(previewColumns(page).first().locator('li')).toHaveCount(MIN_PAIRS);
        await expect(previewColumns(page).last().locator('li')).toHaveCount(MIN_PAIRS);
    });

    test('offers the language taught and the translation language, defaulting to Bulgarian and English', async ({ page }) => {
        await expect(languageSelect(page).locator('option:checked')).toHaveText('Bulgarian');
        await expect(translationLanguageSelect(page).locator('option:checked')).toHaveText('English');
    });

    test('labels the two cells of a pair with the languages chosen', async ({ page }) => {
        await languageSelect(page).selectOption({ label: 'English' });
        await translationLanguageSelect(page).selectOption({ label: 'Bulgarian' });

        const cells = page.locator('.admin-form__input--row');
        await expect(cells.nth(0)).toHaveAttribute('placeholder', 'Bulgarian');
        await expect(cells.nth(1)).toHaveAttribute('placeholder', 'English');
    });

    test('hides the translation language for the other types', async ({ page }) => {
        await page
            .locator('select')
            .filter({ has: page.getByRole('option', { name: 'True/False' }) })
            .selectOption('true_false');

        await expect(languageSelect(page)).toBeVisible();
        await expect(translationLanguageSelect(page)).toHaveCount(0);
    });

    test('the server rejects translating a language into itself', async ({ page }) => {
        await page.getByPlaceholder('Exercise name').fill('E2E same language');
        await fillPairs(page);
        await page.getByPlaceholder('Explain the correct answer').fill('Match each word to its translation.');
        await translationLanguageSelect(page).selectOption({ label: 'Bulgarian' });

        await submit(page).click();

        await expect(page.getByText(/must be different/)).toBeVisible();
    });

    test('the server rejects a duplicated word', async ({ page }) => {
        await page.getByPlaceholder('Exercise name').fill('E2E duplicate words');
        await fillPairs(page);
        await translationInputs(page).last().fill(await translationInputs(page).first().inputValue());
        await page.getByPlaceholder('Explain the correct answer').fill('Match each word to its translation.');

        await submit(page).click();

        await expect(page.getByText('Each word may only appear once in the first column.')).toBeVisible();
    });
});
