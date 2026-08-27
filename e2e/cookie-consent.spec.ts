import { test, expect, Page, BrowserContext } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// vanilla-cookieconsent refuses to render for anything that looks like a bot,
// and an automated browser sets navigator.webdriver, so every spec here has to
// mask it before the app boots or the banner never appears at all.
async function hideAutomation(page: Page): Promise<void> {
    await page.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver', { get: () => false });
    });
}

// The stored consent, as the plugin writes it.
async function consentedCategories(context: BrowserContext): Promise<string[] | null> {
    const cookie = (await context.cookies()).find((c) => c.name === 'cc_cookie');

    if (!cookie) return null;

    return JSON.parse(decodeURIComponent(cookie.value)).categories;
}

const banner = (page: Page) => page.locator('#cc-main .cm');
const preferences = (page: Page) => page.locator('#cc-main .pm');

test.describe('Cookie consent', () => {
    test.beforeEach(async ({ page }) => {
        await hideAutomation(page);
        await page.goto(`${BASE}/`);
    });

    test('greets a first-time visitor with the banner', async ({ page }) => {
        await expect(banner(page)).toBeVisible();
        await expect(page.locator('#cc-main .cm__title')).toHaveText('Cookies on BalkanBuddy');
        await expect(page.getByRole('button', { name: 'Accept all' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Reject optional' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Choose what to allow' })).toBeVisible();
    });

    test('accepting everything stores the analytics category', async ({ page, context }) => {
        await page.getByRole('button', { name: 'Accept all' }).first().click();

        await expect(banner(page)).toBeHidden();
        expect(await consentedCategories(context)).toEqual(expect.arrayContaining(['necessary', 'analytics']));
    });

    test('rejecting the optional category stores only the necessary one', async ({ page, context }) => {
        await page.getByRole('button', { name: 'Reject optional' }).first().click();

        await expect(banner(page)).toBeHidden();
        expect(await consentedCategories(context)).toEqual(['necessary']);
    });

    test('the preferences dialog explains both categories', async ({ page }) => {
        await page.getByRole('button', { name: 'Choose what to allow' }).click();

        await expect(preferences(page)).toBeVisible();
        await expect(page.locator('#cc-main .pm__section-title')).toHaveText([
            'How we use cookies',
            'Strictly necessary',
            'Performance measurement',
        ]);
        await expect(page.getByRole('button', { name: 'Save my choices' })).toBeVisible();
    });

    test('a decision keeps the banner away on the next visit', async ({ page }) => {
        await page.getByRole('button', { name: 'Reject optional' }).first().click();
        await expect(banner(page)).toBeHidden();

        await page.goto(`${BASE}/`);

        await expect(banner(page)).toBeHidden();
    });

    // The modals read their palette from cc--darkmode, which useTheme keeps on
    // the html element alongside its own dark/light classes.
    test('the modals follow the page theme', async ({ page }) => {
        const html = page.locator('html');

        await expect(html).toHaveClass(/cc--darkmode/);

        await page.locator('.nb-page button[title*="Switch to light"]').first().click();

        await expect(html).not.toHaveClass(/cc--darkmode/);
    });

    test('the site no longer ships the previous cookie bar', async ({ page }) => {
        await expect(page.getByText('Your experience on this site will be improved')).toHaveCount(0);
    });
});
