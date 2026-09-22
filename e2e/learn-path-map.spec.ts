import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// The student has the path's first lesson finished (E2eSeeder); the admin has
// finished nothing on it, which is what makes them the control in the
// per-viewer test below.
const STUDENT = process.env.E2E_USER_EMAIL ?? '';
const ADMIN = process.env.E2E_ADMIN_EMAIL ?? '';
const PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';
const PATH_ID = process.env.E2E_FIRST_PATH_ID ?? '';

// Logs in through the UI; returns false when the fixture user is unavailable
// so callers can skip instead of failing on fixture-less environments.
async function login(page: Page, email: string): Promise<boolean> {
    if (!email) return false;
    await page.goto(`${BASE}/login`);
    await page.fill('#email', email);
    await page.fill('#password', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('login'), { timeout: 15000 }).catch(() => {});
    return !page.url().includes('/login');
}

// Opens the path map and waits for its nodes, which are placed from a measured
// container width and so are absent from the DOM on first paint.
async function openMap(page: Page): Promise<void> {
    await page.goto(`${BASE}/learning-paths/${PATH_ID}`);
    await expect(page.locator('.stmap__node').first()).toBeVisible();
}

/**
 * These read the student's seeded progress without spending it: nothing here
 * completes or restarts anything, so lesson-restart.spec.ts still finds the
 * finished lesson it needs. That spec clears the progress in its last test,
 * and with workers=1 on CI the files run in alphabetical order, which puts
 * this one first — keep the name sorting before "lesson-".
 */
test.describe('Learning path map', () => {
    test.beforeEach(async () => {
        test.skip(!PATH_ID, 'set E2E_FIRST_PATH_ID to the path the student is enrolled in');
    });

    test('marks the lesson the student finished as done and pins the next one', async ({ page }) => {
        test.skip(!(await login(page, STUDENT)), 'seeded student is unavailable in this environment');
        await openMap(page);

        await expect(page.locator('.stmap__node--done')).toHaveCount(1);
        await expect(page.locator('.stmap__node').first()).toHaveClass(/stmap__node--done/);

        await expect(page.locator('.stmap__pin')).toHaveCount(1);
        await expect(page.locator('.stmap__node').nth(1)).toHaveClass(/stmap__node--current/);
    });

    test('draws the finished leg of the route as done', async ({ page }) => {
        test.skip(!(await login(page, STUDENT)), 'seeded student is unavailable in this environment');
        await openMap(page);

        await expect(page.locator('.stmap__seg--done')).toHaveCount(1);
        await expect(page.locator('.stmap__seg--todo')).not.toHaveCount(0);
    });

    // The regression that took completion off learning_path_lesson: the flag
    // hung on the path and the lesson with no user in its key, so one
    // student's progress showed on every other account opening the same path.
    test('shows the student\'s progress to nobody else', async ({ page }) => {
        test.skip(!(await login(page, ADMIN)), 'seeded admin is unavailable in this environment');
        await openMap(page);

        await expect(page.locator('.stmap__node')).not.toHaveCount(0);
        await expect(page.locator('.stmap__node--done')).toHaveCount(0);
        await expect(page.locator('.stmap__node').first()).toHaveClass(/stmap__node--current/);
    });
});
