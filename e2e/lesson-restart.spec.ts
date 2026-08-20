import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded credentials (database/seeders/DatabaseSeeder.php); override via env
// when running against an environment with different fixtures.
const EMAIL = process.env.E2E_USER_EMAIL ?? 'test@example.com';
const PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

// The restart prompt only renders for a lesson the signed-in user has already
// finished, so which lesson that is has to come from the environment.
const LESSON_ID = process.env.E2E_COMPLETED_LESSON_ID ?? '';

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

// LessonController::show redirects to the first unfinished exercise unless the
// whole lesson is done, so staying on /lesson/{id} means the prompt is up.
async function openRestartPrompt(page: Page): Promise<boolean> {
    if (!LESSON_ID) return false;
    await page.goto(`${BASE}/lesson/${LESSON_ID}`);
    return new URL(page.url()).pathname === `/lesson/${LESSON_ID}`;
}

// Serial: the last test restarts the lesson, which clears the completions the
// earlier tests depend on to reach the prompt at all.
test.describe.configure({ mode: 'serial' });

test.describe('Lesson restart prompt', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');
        test.skip(
            !(await openRestartPrompt(page)),
            'set E2E_COMPLETED_LESSON_ID to a lesson this user has finished',
        );
    });

    test('renders the neo-brutalist shell with heading and badge', async ({ page }) => {
        await expect(page.locator('.nb-lesson')).toBeVisible();
        await expect(page.locator('.nb-lesson__card')).toBeVisible();
        await expect(page.locator('.nb-lesson__icon svg')).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Restart lesson?' })).toBeVisible();
        await expect(page.locator('.nb-lesson__badge')).toHaveText('Урок');
        await expect(page.locator('.nb-lesson__meta')).toHaveText('Completed');
    });

    test('names the finished lesson in the highlighted chip', async ({ page }) => {
        const name = page.locator('.nb-lesson__name');

        await expect(name).toBeVisible();
        await expect(name).not.toBeEmpty();
        await expect(page.locator('.nb-lesson__body')).toContainText("You've already completed");
    });

    test('offers a restart and a go-back action', async ({ page }) => {
        const actions = page.locator('.nb-lesson__action');

        await expect(actions).toHaveCount(2);
        await expect(page.getByRole('button', { name: 'Yes, restart' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'No, go back' })).toBeVisible();
        await expect(actions.first()).toHaveClass(/nb-lesson__action--primary/);
    });

    test('theme toggle switches between dark and light', async ({ page }) => {
        const shell = page.locator('.nb-lesson');
        const toggle = page.locator('.nb-lesson__toggle');

        const startedDark = await shell.evaluate((el) => el.classList.contains('dark'));

        await toggle.click();
        await expect(shell).toHaveClass(startedDark ? /light/ : /dark/);

        await toggle.click();
        await expect(shell).toHaveClass(startedDark ? /dark/ : /light/);
    });

    test('"No, go back" returns to the previous page', async ({ page }) => {
        await page.goto(`${BASE}/learning-paths`);
        await page.goto(`${BASE}/lesson/${LESSON_ID}`);

        await page.getByRole('button', { name: 'No, go back' }).click();

        await expect(page).toHaveURL(`${BASE}/learning-paths`);
    });

    // Runs last: this clears the completions that put the prompt on screen.
    test('"Yes, restart" clears progress and reopens the first exercise', async ({ page }) => {
        await page.getByRole('button', { name: 'Yes, restart' }).click();

        await page.waitForURL((url) => url.pathname.startsWith('/exercise/'), { timeout: 15000 });
        await expect(page.locator('.nb-ex__count')).toHaveText(/^0 \/ \d+$/);
    });
});
