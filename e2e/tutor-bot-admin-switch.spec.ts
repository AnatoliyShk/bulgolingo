import { test, expect, type Page, type Route } from '@playwright/test';

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

const container = (page: Page) => page.locator('.admin-tutor-switch');
const toggle = (page: Page) => container(page).getByRole('switch', { name: 'Tutor bot' });

// Flipping the tutor for real would switch it off under the tutor-bot spec
// running in parallel, so the save is answered here: the payload is recorded
// and the welcome page the browser holds comes back with tutorEnabled set to
// what was asked for, the way the real redirect back would render it. WebKit
// refuses to fulfil with a 3xx, so the Inertia answer is returned directly.
// `delayMs` holds the answer back to leave the in-flight state observable.
async function interceptSwitch(page: Page, delayMs = 0): Promise<Record<string, unknown>[]> {
    const payloads: Record<string, unknown>[] = [];
    const current = await page.evaluate(() => {
        const script = document.querySelector('script[data-page="app"]');

        return script ? script.textContent : document.getElementById('app')?.dataset.page;
    }) ?? '{}';

    await page.route(`${BASE}/admin/settings/tutor-bot`, async (route: Route) => {
        const payload = JSON.parse(route.request().postData() ?? '{}');
        payloads.push(payload);

        if (delayMs) {
            await new Promise((resolve) => setTimeout(resolve, delayMs));
        }

        const body = JSON.parse(current);
        body.props.tutorEnabled = payload.enabled;

        await route.fulfill({
            status: 200,
            headers: { 'Content-Type': 'application/json', 'X-Inertia': 'true', Vary: 'X-Inertia' },
            body: JSON.stringify(body),
        });
    });

    return payloads;
}

test.describe('Tutor bot admin switch', () => {
    test('is hidden from guests', async ({ page }) => {
        await page.goto(`${BASE}/`);

        await expect(container(page)).toHaveCount(0);
    });

    test('is hidden from a non-admin', async ({ page }) => {
        test.skip(!(await login(page, USER_EMAIL, USER_PASSWORD)), 'seeded non-admin user is unavailable in this environment');
        await page.goto(`${BASE}/`);

        await expect(container(page)).toHaveCount(0);
    });

    // The seeder turns the tutor on, so the admin opens the page with the
    // switch on and the launcher showing; one click sends enabled=false and
    // the launcher goes, a second sends enabled=true and brings it back.
    test('turns the tutor off and on again for an admin', async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin user is unavailable in this environment');
        await page.goto(`${BASE}/`);

        await expect(toggle(page)).toHaveAttribute('aria-checked', 'true');
        await expect(container(page).locator('.admin-tutor-switch__state')).toHaveText('On');
        await expect(page.locator('.nb-tutor__launcher')).toBeVisible();

        const payloads = await interceptSwitch(page);

        await toggle(page).click();
        await expect(toggle(page)).toHaveAttribute('aria-checked', 'false');
        await expect(container(page).locator('.admin-tutor-switch__state')).toHaveText('Off');
        await expect(page.locator('.nb-tutor__launcher')).toHaveCount(0);

        await toggle(page).click();
        await expect(toggle(page)).toHaveAttribute('aria-checked', 'true');
        await expect(page.locator('.nb-tutor__launcher')).toBeVisible();

        expect(payloads).toEqual([{ enabled: false }, { enabled: true }]);
    });

    // While a save is in flight the switch is disabled, so a double click
    // cannot send two opposite requests that race each other.
    test('is disabled while a save is in flight', async ({ page }) => {
        test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin user is unavailable in this environment');
        await page.goto(`${BASE}/`);

        const payloads = await interceptSwitch(page, 800);

        await toggle(page).click();
        await expect(toggle(page)).toBeDisabled();
        await expect(toggle(page)).toBeEnabled();

        expect(payloads).toHaveLength(1);
    });
});
