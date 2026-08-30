import { test, expect } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

test.describe('Welcome page', () => {
    test('is browsable without logging in', async ({ page }) => {
        await page.goto(`${BASE}/`);

        await expect(page).toHaveURL(`${BASE}/`);
        await expect(page.getByRole('link', { name: 'Register' })).toBeVisible();
    });
});
