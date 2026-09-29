import { test, expect, Page, Route } from '@playwright/test';

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

async function openForm(page: Page): Promise<void> {
    test.skip(!(await login(page, ADMIN_EMAIL, ADMIN_PASSWORD)), 'seeded admin user is unavailable in this environment');
    await page.goto(`${BASE}/admin/settings`);
    await expect(page.getByRole('heading', { name: 'Running line' })).toBeVisible();
}

const form = (page: Page) => page.locator('.admin-marquee-form');
const rows = (page: Page) => form(page).locator('.admin-marquee-form__row');
const bulgarian = (page: Page, n: number) => form(page).getByLabel(`Bulgarian word ${n}`, { exact: true });
const english = (page: Page, n: number) => form(page).getByLabel(`English meaning ${n}`, { exact: true });
const remove = (page: Page, n: number) => form(page).getByRole('button', { name: `Remove word ${n}`, exact: true });
const addWord = (page: Page) => form(page).getByRole('button', { name: 'Add a word' });
const save = (page: Page) => form(page).getByRole('button', { name: /Save running line|Saving/ });

// Saving for real would replace the words under every spec running in
// parallel, the welcome page ones among them, so the save is answered here:
// the payload is recorded and the page the browser already holds is handed
// straight back as the Inertia answer. WebKit refuses to fulfil a route with
// a 3xx, so the real controller's redirect is not reproduced. `delayMs` holds
// the answer back to leave the in-flight state observable.
async function interceptSave(page: Page, delayMs = 0): Promise<Record<string, unknown>[]> {
    const payloads: Record<string, unknown>[] = [];
    const body = await page.evaluate(() => {
        const script = document.querySelector('script[data-page="app"]');

        return script ? script.textContent : document.getElementById('app')?.dataset.page;
    }) ?? '{}';

    await page.route(`${BASE}/admin/settings/marquee`, async (route: Route) => {
        payloads.push(JSON.parse(route.request().postData() ?? '{}'));

        if (delayMs) {
            await new Promise((resolve) => setTimeout(resolve, delayMs));
        }

        await route.fulfill({
            status: 200,
            headers: { 'Content-Type': 'application/json', 'X-Inertia': 'true', Vary: 'X-Inertia' },
            body,
        });
    });

    return payloads;
}

test.describe('Admin running line form', () => {
    test('is hidden from a guest, who is sent to log in', async ({ page }) => {
        await page.goto(`${BASE}/admin/settings`);

        await expect(page).toHaveURL(`${BASE}/login`);
        await expect(form(page)).toHaveCount(0);
    });

    test('lists every stored word as a Bulgarian and English pair', async ({ page }) => {
        await openForm(page);

        expect(await rows(page).count()).toBeGreaterThan(0);
        await expect(bulgarian(page, 1)).not.toHaveValue('');
        await expect(english(page, 1)).not.toHaveValue('');
        await expect(bulgarian(page, 1)).toHaveAttribute('lang', 'bg');
        await expect(rows(page).first().locator('.admin-marquee-form__num')).toHaveText('1');
    });

    test('Add a word appends a blank pair at the end', async ({ page }) => {
        await openForm(page);
        const before = await rows(page).count();

        await addWord(page).click();

        await expect(rows(page)).toHaveCount(before + 1);
        await expect(bulgarian(page, before + 1)).toHaveValue('');
        await expect(english(page, before + 1)).toHaveValue('');
    });

    test('Remove drops that pair and the numbering closes up', async ({ page }) => {
        await openForm(page);
        const before = await rows(page).count();
        test.skip(before < 2, 'needs at least two stored words');
        const second = await bulgarian(page, 2).inputValue();

        await remove(page, 1).click();

        await expect(rows(page)).toHaveCount(before - 1);
        await expect(bulgarian(page, 1)).toHaveValue(second);
    });

    test('the last pair cannot be removed', async ({ page }) => {
        await openForm(page);

        while ((await rows(page).count()) > 1) {
            await remove(page, 1).click();
        }

        await expect(rows(page)).toHaveCount(1);
        await expect(remove(page, 1)).toBeDisabled();

        await addWord(page).click();
        await expect(remove(page, 1)).toBeEnabled();
    });

    test('saving sends the edited list, then says it saved', async ({ page }) => {
        await openForm(page);
        const payloads = await interceptSave(page);

        await bulgarian(page, 1).fill('Тест');
        await english(page, 1).fill('test');
        await addWord(page).click();
        const last = await rows(page).count();
        await bulgarian(page, last).fill('Нова');
        await english(page, last).fill('new');
        await save(page).click();

        await expect(form(page).getByRole('status')).toHaveText('Saved.');
        expect(payloads).toHaveLength(1);
        const words = payloads[0].words as { bg: string; en: string }[];
        expect(words).toHaveLength(last);
        expect(words[0]).toEqual({ bg: 'Тест', en: 'test' });
        expect(words[last - 1]).toEqual({ bg: 'Нова', en: 'new' });
    });

    test('disables the save button while saving', async ({ page }) => {
        await openForm(page);
        await interceptSave(page, 800);

        await save(page).click();

        await expect(form(page).getByRole('button', { name: 'Saving…' })).toBeDisabled();
        await expect(save(page)).toBeEnabled();
    });

    // The blank pair reaches the real server, which rejects it in validation,
    // so nothing is stored and parallel specs are unaffected.
    test('shows the server\'s reason for a blank word', async ({ page }) => {
        await openForm(page);
        await addWord(page).click();
        const last = await rows(page).count();

        await save(page).click();

        await expect(form(page).getByText('Every word needs its Bulgarian text.')).toBeVisible();
        await expect(form(page).getByText('Every word needs its English meaning.')).toBeVisible();
        await expect(bulgarian(page, last)).toHaveAttribute('aria-invalid', 'true');
        await expect(english(page, last)).toHaveAttribute('aria-invalid', 'true');
        await expect(form(page).getByRole('status')).toHaveCount(0);
    });

    test('the input fields stop a word over 60 characters', async ({ page }) => {
        await openForm(page);

        await expect(bulgarian(page, 1)).toHaveAttribute('maxlength', '60');
        await expect(english(page, 1)).toHaveAttribute('maxlength', '60');
    });

    test('the welcome page runs each stored word twice for a seamless loop', async ({ page }) => {
        await openForm(page);
        const count = await rows(page).count();
        const first = await bulgarian(page, 1).inputValue();

        await page.goto(`${BASE}/`);

        await expect(page.locator('.nb-marquee__item')).toHaveCount(count * 2);
        await expect(page.locator('.nb-marquee__item').first()).toContainText(first);
    });
});
