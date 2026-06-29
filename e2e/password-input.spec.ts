import { test, expect } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Exercises the reusable PasswordInput show/hide toggle on the login form.
test.describe('PasswordInput show/hide toggle', () => {
    test('toggles the password field between hidden and visible', async ({ page }) => {
        await page.goto(`${BASE}/login`);

        const password = page.locator('#password');
        const showBtn = page.getByRole('button', { name: 'Show password' });

        // Hidden by default.
        await expect(password).toHaveAttribute('type', 'password');
        await expect(showBtn).toBeVisible();

        // Reveal.
        await showBtn.click();
        await expect(password).toHaveAttribute('type', 'text');

        // Button now offers to hide again.
        const hideBtn = page.getByRole('button', { name: 'Hide password' });
        await expect(hideBtn).toBeVisible();

        // Hide.
        await hideBtn.click();
        await expect(password).toHaveAttribute('type', 'password');
        await expect(page.getByRole('button', { name: 'Show password' })).toBeVisible();
    });

    test('reveals the value the user typed', async ({ page }) => {
        await page.goto(`${BASE}/login`);

        const password = page.locator('#password');
        await password.fill('s3cr3t-pw');

        await page.getByRole('button', { name: 'Show password' }).click();

        await expect(password).toHaveAttribute('type', 'text');
        await expect(password).toHaveValue('s3cr3t-pw');
    });
});
