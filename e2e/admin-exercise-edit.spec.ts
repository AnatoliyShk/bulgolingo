import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, created for admin-metrics-page verification; reused here.
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

// Which word-pair exercise to edit has to come from the environment.
const EXERCISE_ID = process.env.E2E_WORD_PAIR_EXERCISE_ID ?? '';

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

test.describe('Admin word pair edit form', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin test user is unavailable in this environment');
        test.skip(!EXERCISE_ID, 'set E2E_WORD_PAIR_EXERCISE_ID to a word pair exercise');

        await page.goto(`${BASE}/admin/exercises/${EXERCISE_ID}/edit`);
        await expect(wordInputs(page).first()).toBeVisible();
    });

    test('opens with the stored pairs', async ({ page }) => {
        await expect(wordInputs(page)).toHaveCount(MIN_PAIRS);
        await expect(page.getByText(`${MIN_PAIRS} pairs · ${MIN_PAIRS * 2} words`)).toBeVisible();
    });

    // The server deals the columns on every save here, so offering the admin a
    // Shuffle button or a preview of an order would only promise something the
    // save is about to replace.
    test('offers no shuffle control, only the notice that saving deals again', async ({ page }) => {
        await expect(page.getByRole('button', { name: 'Shuffle' })).toHaveCount(0);
        await expect(page.getByText('Order the student sees')).toHaveCount(0);
        await expect(page.getByText('Saving deals a new order for both columns.')).toBeVisible();
    });

    test('still holds the pair count to the minimum', async ({ page }) => {
        for (const button of await page.getByRole('button', { name: 'Remove' }).all()) {
            await expect(button).toBeDisabled();
        }
    });
});
