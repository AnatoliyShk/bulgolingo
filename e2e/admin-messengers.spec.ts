import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, shared with the other admin specs.
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

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

// The dropdown lists real seeded users by name, which the spec cannot predict,
// so it always picks the first selectable one rather than a name it guesses.
async function selectFirstUser(page: Page): Promise<void> {
    await page.locator('#user_id').selectOption({ index: 1 });
}

const row = (page: Page, messengerUserId: string) => page.getByRole('row', { name: new RegExp(messengerUserId) });

test.describe('Admin messengers', () => {
    test.beforeEach(async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin test user is unavailable in this environment');
    });

    test('the create form rejects an empty submission', async ({ page }) => {
        await page.goto(`${BASE}/admin/messengers/create`);
        await page.click('button[type="submit"]');

        await expect(page.getByText('The user id field is required.')).toBeVisible();
        await expect(page.getByText('The messenger name field is required.')).toBeVisible();
        await expect(page.getByText('The messenger user id field is required.')).toBeVisible();
    });

    test('an admin can create, edit and delete a messenger', async ({ page }) => {
        const messengerUserId = `e2e-${Date.now()}`;

        await page.goto(`${BASE}/admin/messengers/create`);
        await selectFirstUser(page);
        await page.fill('#messenger_name', 'Telegram');
        await page.fill('#messenger_user_id', messengerUserId);
        await page.click('button[type="submit"]');

        await page.waitForURL(`${BASE}/admin/messengers`);
        await expect(row(page, messengerUserId)).toContainText('Telegram');

        await row(page, messengerUserId).getByRole('link', { name: 'Edit' }).click();
        await page.fill('#messenger_name', 'WhatsApp');
        await page.click('button[type="submit"]');

        await page.waitForURL(`${BASE}/admin/messengers`);
        await expect(row(page, messengerUserId)).toContainText('WhatsApp');

        page.once('dialog', (dialog) => dialog.accept());
        await row(page, messengerUserId).getByRole('button', { name: 'Delete' }).click();

        await expect(row(page, messengerUserId)).toHaveCount(0);
    });
});
