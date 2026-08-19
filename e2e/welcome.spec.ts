import { test, expect } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

test.describe('Welcome page', () => {
    test('is browsable without logging in', async ({ page }) => {
        await page.goto(`${BASE}/`);

        await expect(page).toHaveURL(`${BASE}/`);
        await expect(page.getByRole('link', { name: 'Register' })).toBeVisible();
    });

    test('mobile viewport collapses nav behind a hamburger toggle', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 800 });
        await page.goto(`${BASE}/`);

        const hamburger = page.locator('.nb-hamburger');
        const toggle = page.locator('.nb-toggle');
        const links = page.locator('.nb-nav__links');

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
        await page.goto(`${BASE}/`);

        await expect(page.locator('.nb-hamburger')).not.toBeVisible();
        await expect(page.getByRole('link', { name: 'Register' })).toBeVisible();
    });
});
