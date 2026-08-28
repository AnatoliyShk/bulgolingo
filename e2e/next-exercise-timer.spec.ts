import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded credentials (database/seeders/DatabaseSeeder.php); override via env
// when running against an environment with different fixtures.
const EMAIL = process.env.E2E_USER_EMAIL ?? 'test@example.com';
const PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

// An image matching exercise the signed-in user has not finished yet. Which
// one that is depends on the fixtures, so it comes from the environment.
const EXERCISE_ID = process.env.E2E_IMAGE_MATCHING_EXERCISE_ID ?? '';

// How long NextExerciseButton waits before advancing on its own.
const COUNTDOWN_MS = 5000;

// Logs in through the UI; returns false when the seeded user is unavailable
// so callers can skip instead of failing on fixture-less environments.
async function login(page: Page): Promise<boolean> {
    await page.goto(`${BASE}/login`);
    await page.fill('#email', EMAIL);
    await page.fill('#password', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('login'), { timeout: 15000 }).catch(() => {});
    return !page.url().includes('/login');
}

// Works through the options until one is accepted, which is what puts the
// countdown on screen. The spec cannot know which sentence matches the picture,
// and a wrong pick only costs a click on Try Again, so trying them in turn
// keeps the test free of answer fixtures.
async function answerCorrectly(page: Page): Promise<boolean> {
    const options = page.locator('.nb-ex-opt');
    const countdown = page.locator('.nb-next');
    const total = await options.count();

    for (let index = 0; index < total; index++) {
        await options.nth(index).click();

        if (await countdown.isVisible()) return true;

        const retry = page.getByRole('button', { name: 'Try Again' });
        if (await retry.isVisible()) await retry.click();
    }

    return false;
}

test.describe('Next exercise countdown', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');
        test.skip(
            !EXERCISE_ID,
            'set E2E_IMAGE_MATCHING_EXERCISE_ID to an image matching exercise this user has not finished',
        );

        await page.goto(`${BASE}/exercise/${EXERCISE_ID}`);
        test.skip(!(await answerCorrectly(page)), 'no option on this exercise was accepted as correct');
    });

    // The line is the only sign of how much of the pause is left, so it has to
    // be on screen and sitting above the button it belongs to.
    test('puts a countdown line above the next exercise button', async ({ page }) => {
        const timer = page.locator('.nb-next__timer');
        const button = page.locator('.nb-next .nb-ex-action');

        await expect(timer).toBeVisible();
        await expect(button).toBeVisible();
        await expect(button).toHaveText('Next Exercise');

        const line = await timer.boundingBox();
        const control = await button.boundingBox();

        expect(line).not.toBeNull();
        expect(control).not.toBeNull();
        expect(line!.y + line!.height).toBeLessThanOrEqual(control!.y);
    });

    test('drains the line while the countdown runs', async ({ page }) => {
        const fill = page.locator('.nb-next__timer-fill');

        const started = (await fill.boundingBox())!.width;
        await page.waitForTimeout(1500);
        const later = (await fill.boundingBox())!.width;

        expect(later).toBeLessThan(started);
    });

    // Clicking is the fast forward: it must not wait out the rest of the pause.
    test('advances at once when the button is clicked', async ({ page }) => {
        const from = page.url();
        const startedAt = Date.now();

        await page.locator('.nb-next .nb-ex-action').click();
        await page.waitForURL((url) => url.href !== from, { timeout: 15000 });

        expect(Date.now() - startedAt).toBeLessThan(COUNTDOWN_MS);
    });

    // Left alone, the countdown moves the learner on by itself — and only once
    // the pause it showed has actually run out.
    test('advances on its own once the countdown runs out', async ({ page }) => {
        const from = page.url();
        const startedAt = Date.now();

        await page.waitForURL((url) => url.href !== from, { timeout: COUNTDOWN_MS + 10000 });

        expect(Date.now() - startedAt).toBeGreaterThanOrEqual(COUNTDOWN_MS - 1000);
    });
});
