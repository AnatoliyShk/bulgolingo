import { test, expect } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Exercises the public learning-paths catalog: guest access plus the
// per-card row of exercise-type icons.
test.describe('Learning paths catalog', () => {
    test('is browsable without logging in', async ({ page }) => {
        await page.goto(`${BASE}/learning-paths`);

        await expect(page).toHaveURL(`${BASE}/learning-paths`);
        await expect(page.getByRole('heading', { name: 'All learning paths' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Register' })).toBeVisible();
    });

    test('shows an icon for each exercise type offered by a path', async ({ page }) => {
        await page.goto(`${BASE}/learning-paths`);

        const cards = page.locator('.nb-paths__card');
        test.skip((await cards.count()) === 0, 'no learning paths seeded in this environment');

        const types = cards.first().locator('.nb-paths__card-type');
        test.skip((await types.count()) === 0, 'first learning path has no exercises seeded');

        const firstType = types.first();
        await expect(firstType).toHaveAttribute('title', /.+/);
        await expect(firstType.locator('svg')).toBeVisible();
    });

    test('mobile viewport collapses nav behind a hamburger toggle', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 800 });
        await page.goto(`${BASE}/learning-paths`);

        const hamburger = page.locator('.nb-paths__hamburger');
        const toggle = page.locator('.nb-toggle');
        const links = page.locator('.nb-paths__bar-links');

        await expect(hamburger).toBeVisible();
        await expect(toggle).toBeVisible();
        await expect(links).not.toBeVisible();

        await hamburger.click();
        await expect(links).toBeVisible();
        await expect(page.getByRole('link', { name: 'Register' })).toBeVisible();

        await hamburger.click();
        await expect(links).not.toBeVisible();
    });

    test('desktop viewport shows nav links inline without a hamburger', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 800 });
        await page.goto(`${BASE}/learning-paths`);

        await expect(page.locator('.nb-paths__hamburger')).not.toBeVisible();
        await expect(page.getByRole('link', { name: 'Register' })).toBeVisible();
    });
});
