import { test, expect, Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Seeded credentials (database/seeders/DatabaseSeeder.php); override via env
// when running against an environment with different fixtures.
const EMAIL = process.env.E2E_USER_EMAIL ?? 'test@example.com';
const PASSWORD = process.env.E2E_USER_PASSWORD ?? 'password';

const GUEST_PAGES = ['/', '/login', '/register', '/forgot-password'];
const MEMBER_PAGES = ['/dashboard', '/profile', '/stats', '/learning-paths'];

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

// The properties that make the button recognisable as the same control from
// page to page. The border and shadow colours are left out on purpose: they
// follow each page's own ink, which is what keeps the button readable on the
// screens that ship their own palette.
async function appearance(page: Page): Promise<string> {
    return page.locator('.nb-toggle').first().evaluate((node) => {
        const style = getComputedStyle(node);
        const box = node.getBoundingClientRect();

        return [
            `${Math.round(box.width)}x${Math.round(box.height)}`,
            style.backgroundColor,
            style.color,
            style.borderTopWidth,
            style.borderTopLeftRadius,
            style.fontSize,
        ].join(' | ');
    });
}

// Reads the theme the page is actually wearing, which the composable writes to
// the html element for every page to inherit.
async function currentTheme(page: Page): Promise<string> {
    return page.evaluate(() => document.documentElement.className);
}

test.describe('Theme toggle', () => {
    test('every guest page carries one identically styled toggle', async ({ page }) => {
        const looks: string[] = [];

        for (const path of GUEST_PAGES) {
            await page.goto(`${BASE}${path}`);
            await expect(page.locator('.nb-toggle')).toHaveCount(1);
            looks.push(await appearance(page));
        }

        expect(new Set(looks).size).toBe(1);
    });

    test('switches the theme, the glyph and the label on click', async ({ page }) => {
        await page.goto(`${BASE}/`);

        const toggle = page.locator('.nb-toggle');
        const startedDark = (await currentTheme(page)).includes('dark');

        await expect(toggle).toHaveText(startedDark ? '☀' : '☾');
        await expect(toggle).toHaveAttribute('title', startedDark ? 'Switch to light' : 'Switch to dark');
        await expect(toggle).toHaveAttribute(
            'aria-label',
            startedDark ? 'Switch to light theme' : 'Switch to dark theme',
        );

        await toggle.click();

        await expect(page.locator('html')).toHaveClass(startedDark ? /light/ : /dark/);
        await expect(toggle).toHaveText(startedDark ? '☾' : '☀');
        await expect(toggle).toHaveAttribute('title', startedDark ? 'Switch to dark' : 'Switch to light');

        await toggle.click();

        await expect(page.locator('html')).toHaveClass(startedDark ? /dark/ : /light/);
        await expect(toggle).toHaveText(startedDark ? '☀' : '☾');
    });

    test('remembers the choice across a reload and a page change', async ({ page }) => {
        await page.goto(`${BASE}/`);
        await page.locator('.nb-toggle').click();

        const chosen = await page.evaluate(() => localStorage.getItem('theme'));
        expect(chosen).toBeTruthy();

        await page.reload();
        await expect(page.locator('html')).toHaveClass(new RegExp(chosen as string));

        await page.goto(`${BASE}/login`);
        await expect(page.locator('html')).toHaveClass(new RegExp(chosen as string));
        await expect(page.locator('.nb-toggle')).toHaveText(chosen === 'dark' ? '☀' : '☾');
    });

    test('member pages carry the same toggle as the welcome page', async ({ page }) => {
        await page.goto(`${BASE}/`);
        const welcomeLook = await appearance(page);

        test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');

        for (const path of MEMBER_PAGES) {
            await page.goto(`${BASE}${path}`);
            await expect(page.locator('.nb-toggle')).toHaveCount(1);
            expect(await appearance(page), `${path} toggle differs`).toBe(welcomeLook);
        }
    });

    test('toggling on one page carries over to the next', async ({ page }) => {
        test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');

        await page.goto(`${BASE}/stats`);
        await page.locator('.nb-toggle').click();
        const afterClick = await currentTheme(page);

        await page.goto(`${BASE}/learning-paths`);
        expect(await currentTheme(page)).toBe(afterClick);
    });
});
