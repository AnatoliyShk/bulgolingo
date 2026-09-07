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

const streak = (page: Page) => page.getByTestId('profile-streak');
const avatar = (page: Page) => page.locator('.nb-prof__avatar');

test.describe('Profile streak chip', () => {
    test('redirects guests to the login page', async ({ page }) => {
        await page.goto(`${BASE}/dashboard`);
        await expect(page).toHaveURL(`${BASE}/login`);
    });

    test.describe('when authenticated', () => {
        // Inertia renders client-side, so the chip has to exist before anything
        // reads a class or a computed colour off it.
        test.beforeEach(async ({ page }) => {
            test.skip(!(await login(page)), 'seeded test user is unavailable in this environment');

            await page.goto(`${BASE}/dashboard`);
            await page.waitForSelector('[data-testid="profile-streak"]', { timeout: 15000 });
        });

        test('sits in the avatar column beside the face', async ({ page }) => {
            await expect(streak(page)).toBeVisible();

            const chip = await streak(page).boundingBox();
            const face = await avatar(page).boundingBox();

            expect(chip).not.toBeNull();
            expect(face).not.toBeNull();
            expect(Math.abs((chip!.x + chip!.width / 2) - (face!.x + face!.width / 2))).toBeLessThan(60);
        });

        test('is in exactly one of the lit or cold states', async ({ page }) => {
            const cls = (await streak(page).getAttribute('class')) ?? '';
            const lit = cls.includes('nb-prof__streak--lit');
            const cold = cls.includes('nb-prof__streak--cold');

            expect(lit !== cold).toBe(true);
        });

        test('paints the lit state red and the cold state grey', async ({ page }) => {
            const cls = (await streak(page).getAttribute('class')) ?? '';
            const background = await streak(page).evaluate((el) => getComputedStyle(el).backgroundColor);

            const channels = background.match(/\d+/g)?.map(Number) ?? [];
            expect(channels.length).toBeGreaterThanOrEqual(3);

            const [r, g, b] = channels;

            if (cls.includes('nb-prof__streak--lit')) {
                expect(r).toBeGreaterThan(150);
                expect(r - g).toBeGreaterThan(80);
                expect(r - b).toBeGreaterThan(80);
            } else {
                // Grey is grey in both themes: the channels stay close together.
                expect(Math.max(r, g, b) - Math.min(r, g, b)).toBeLessThan(40);
            }
        });

        // The chip carries no text, so the label is the only thing that states
        // what the colour means.
        test('describes its state to assistive technology', async ({ page }) => {
            const label = await streak(page).getAttribute('aria-label');
            const cls = (await streak(page).getAttribute('class')) ?? '';

            expect(label).toBe(cls.includes('nb-prof__streak--lit') ? 'Practised today' : 'Not practised today');
        });
    });
});
