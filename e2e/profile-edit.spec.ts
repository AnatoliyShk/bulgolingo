import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

const USER_EMAIL = process.env.E2E_USER_EMAIL ?? 'e2e-stats@example.com';
const USER_PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

// Logs in through the UI; returns false when the seeded user is unavailable
// so callers can skip instead of failing on fixture-less environments.
async function login(page: Page): Promise<boolean> {
    await page.goto(`${BASE}/login`);
    await page.fill('#email', USER_EMAIL);
    await page.fill('#password', USER_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('login'), { timeout: 15000 }).catch(() => {});
    return !page.url().includes('/login');
}

const sheet = (page: Page) => page.locator('.nb-edit');
const cards = (page: Page) => page.locator('.nb-edit__card');
const dangerCard = (page: Page) => page.locator('.nb-edit__card--danger');

test.describe('Profile edit page', () => {
    test('redirects guests to the login page', async ({ page }) => {
        await page.goto(`${BASE}/profile/edit`);
        await expect(page).toHaveURL(`${BASE}/login`);
    });

    test.describe('when authenticated', () => {
        // Inertia renders client-side, so the sheet has to exist before anything
        // reads a computed style off it.
        test.beforeEach(async ({ page }) => {
            test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');

            await page.goto(`${BASE}/profile/edit`);
            await page.waitForSelector('.nb-edit__card', { timeout: 15000 });
        });

        test('renders the four settings cards', async ({ page }) => {
            await expect(cards(page)).toHaveCount(4);
            await expect(dangerCard(page)).toHaveCount(1);
        });

        test('shows an avatar tile, lettered when no picture is set', async ({ page }) => {
            const tile = page.getByTestId('avatar-preview');
            await expect(tile).toBeVisible();

            const hasImage = await tile.locator('img').count();
            const hasLetter = await tile.locator('.nb-edit__avatar-letter').count();

            expect(hasImage + hasLetter).toBe(1);
        });

        test('cannot save until a file has been chosen', async ({ page }) => {
            const save = page.locator('.nb-edit__card').filter({ hasText: 'Profile Picture' })
                .getByRole('button', { name: 'Save' });

            await expect(save).toBeDisabled();
        });

        // The picked file is previewed from an object URL, so the tile shows what
        // is about to be uploaded rather than what is already stored.
        test('previews the chosen file before it is uploaded', async ({ page }) => {
            await page.locator('input#avatar').setInputFiles({
                name: 'face.png',
                mimeType: 'image/png',
                // 1x1 transparent PNG.
                buffer: Buffer.from(
                    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
                    'base64',
                ),
            });

            const card = page.locator('.nb-edit__card').filter({ hasText: 'Profile Picture' });

            await expect(page.getByTestId('avatar-preview').locator('img')).toBeVisible();
            await expect(card.getByText('face.png')).toBeVisible();
            await expect(card.getByRole('button', { name: 'Save' })).toBeEnabled();
        });

        test('carries the same hard border and offset shadow as the profile', async ({ page }) => {
            const card = cards(page).first();

            expect(await card.evaluate((el) => getComputedStyle(el).borderTopWidth)).toBe('3px');
            expect(await card.evaluate((el) => getComputedStyle(el).boxShadow)).toContain('6px');
        });

        test('styles the text fields as brutalist blocks, not soft inputs', async ({ page }) => {
            const input = page.locator('input#name');

            expect(await input.evaluate((el) => getComputedStyle(el).borderTopWidth)).toBe('3px');
            expect(await input.evaluate((el) => getComputedStyle(el).boxShadow)).not.toBe('none');
        });

        // PasswordInput brings its own .pw-field__input rule to the same element,
        // so this is the check that the page's styling actually won.
        test('styles the password fields the same way', async ({ page }) => {
            const input = page.locator('input#current_password');

            expect(await input.evaluate((el) => getComputedStyle(el).borderTopWidth)).toBe('3px');
        });

        test('marks the destructive card in red', async ({ page }) => {
            const tag = dangerCard(page).locator('.nb-edit__card-tag');
            const background = await tag.evaluate((el) => getComputedStyle(el).backgroundColor);
            const [r, g, b] = background.match(/\d+/g)?.map(Number) ?? [];

            expect(r).toBeGreaterThan(150);
            expect(r - g).toBeGreaterThan(80);
            expect(r - b).toBeGreaterThan(80);
        });

        test('opens the delete confirmation in the same style', async ({ page }) => {
            await dangerCard(page).getByRole('button', { name: 'Delete Account' }).click();

            const modal = page.locator('.nb-edit__modal');
            await expect(modal).toBeVisible();
            await expect(modal.locator('input#password')).toBeVisible();
            await expect(modal.getByRole('button', { name: 'Cancel' })).toBeVisible();
        });

        test('follows the theme toggle', async ({ page }) => {
            const light = await sheet(page).evaluate((el) => getComputedStyle(el).backgroundColor);

            await page.locator('.nb-toggle').click();
            await expect(sheet(page)).toHaveClass(/dark/);

            const dark = await sheet(page).evaluate((el) => getComputedStyle(el).backgroundColor);
            expect(dark).not.toBe(light);
        });
    });
});
