import { test, expect, Page, Route } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded admin credentials, shared with the other admin specs.
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

// Seeded non-admin credentials.
const USER_EMAIL = process.env.E2E_USER_EMAIL ?? 'e2e-stats@example.com';
const USER_PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

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

async function openSettings(page: Page): Promise<void> {
    test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin user is unavailable in this environment');
    await page.goto(`${BASE}/admin/settings`);
    await expect(page.getByRole('heading', { name: 'Embedding search' })).toBeVisible();
}

const toggle = (page: Page) => page.getByRole('switch', { name: 'Search learning paths by meaning' });
const similarity = (page: Page) => page.getByLabel('Minimum similarity');
const save = (page: Page) => page.getByRole('button', { name: 'Save' });

// Saving for real would change the setting under every spec running in
// parallel, the search spec among them, so a save that turns search off is
// answered here instead: the payload is recorded and the browser is sent back
// to the settings page, exactly as the real controller redirects.
async function interceptSave(page: Page): Promise<Record<string, unknown>[]> {
    const payloads: Record<string, unknown>[] = [];

    await page.route(`${BASE}/admin/settings`, async (route: Route) => {
        if (route.request().method() !== 'PUT') {
            return route.continue();
        }

        payloads.push(JSON.parse(route.request().postData() ?? '{}'));
        await route.fulfill({ status: 303, headers: { Location: `${BASE}/admin/settings` } });
    });

    return payloads;
}

test.describe('Admin settings page', () => {
    test('redirects guests to the login page', async ({ page }) => {
        await page.goto(`${BASE}/admin/settings`);
        await expect(page).toHaveURL(`${BASE}/login`);
    });

    test('is forbidden to a non-admin', async ({ page }) => {
        test.skip(!(await login(page, USER_EMAIL, USER_PASSWORD)), 'seeded non-admin user is unavailable in this environment');

        const response = await page.goto(`${BASE}/admin/settings`);
        expect(response?.status()).toBe(403);
    });

    test('is linked from the admin panel', async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin user is unavailable in this environment');

        await page.goto(`${BASE}/admin`);
        await page.getByRole('link', { name: /Settings/ }).click();

        await expect(page).toHaveURL(`${BASE}/admin/settings`);
    });

    test('shows the current settings, search on with its similarity floor', async ({ page }) => {
        await openSettings(page);

        await expect(toggle(page)).toHaveAttribute('aria-checked', 'true');
        await expect(toggle(page)).toHaveClass(/admin-settings__switch--on/);
        await expect(similarity(page)).toHaveValue(/^0(\.\d+)?$|^1$/);
        await expect(page.getByText('When off, the search field is hidden')).toBeVisible();
    });

    test('the switch flips on each click', async ({ page }) => {
        await openSettings(page);
        const initial = await toggle(page).getAttribute('aria-checked');
        const flipped = initial === 'true' ? 'false' : 'true';

        await toggle(page).click();
        await expect(toggle(page)).toHaveAttribute('aria-checked', flipped);

        await toggle(page).click();
        await expect(toggle(page)).toHaveAttribute('aria-checked', initial!);
    });

    test('saving sends the switch and the floor, then says it saved', async ({ page }) => {
        await openSettings(page);
        const payloads = await interceptSave(page);

        await toggle(page).click();
        await similarity(page).fill('0.65');
        await save(page).click();

        await expect(page.getByRole('status')).toHaveText('Saved.');
        expect(payloads).toHaveLength(1);
        expect(payloads[0]).toMatchObject({ embedding_min_similarity: 0.65 });
        expect(typeof payloads[0].embedding_search_enabled).toBe('boolean');
    });

    // The field's own min and max stop the browser from sending an
    // out-of-range floor at all.
    test('the browser refuses to send a floor outside 0 to 1', async ({ page }) => {
        await openSettings(page);
        const payloads = await interceptSave(page);

        await similarity(page).fill('1.5');
        await save(page).click();

        expect(await similarity(page).evaluate((input: HTMLInputElement) => input.validity.rangeOverflow)).toBe(true);
        await expect(page.getByRole('status')).toHaveCount(0);
        expect(payloads).toHaveLength(0);
    });

    // With the browser's check taken off the field, the value reaches the real
    // server, which rejects it in validation — nothing is stored, so parallel
    // specs are unaffected — and the page shows the server's reason.
    test('shows the server\'s reason when a floor outside 0 to 1 gets through', async ({ page }) => {
        await openSettings(page);

        await similarity(page).evaluate((input: HTMLInputElement) => input.removeAttribute('max'));
        await similarity(page).fill('1.5');
        await save(page).click();

        await expect(page.getByText('The minimum similarity must be between 0 and 1.')).toBeVisible();
        await expect(similarity(page)).toHaveAttribute('aria-invalid', 'true');
        await expect(page.getByRole('status')).toHaveCount(0);
    });

    test('disables the save button while saving', async ({ page }) => {
        await openSettings(page);

        await page.route(`${BASE}/admin/settings`, async (route: Route) => {
            if (route.request().method() !== 'PUT') {
                return route.continue();
            }

            await new Promise((resolve) => setTimeout(resolve, 800));
            await route.fulfill({ status: 303, headers: { Location: `${BASE}/admin/settings` } });
        });

        await save(page).click();

        await expect(page.getByRole('button', { name: 'Saving…' })).toBeDisabled();
        await expect(save(page)).toBeEnabled();
    });
});
