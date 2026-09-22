import { test, expect, type Page } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

// Every spec here fulfils POST /tutor itself. The endpoint is a paid
// completion, so a suite that let it through would bill a provider per run and
// assert against whatever the model happened to say.
async function fulfilStream(page: Page, deltas: string[]) {
    await page.route('**/tutor', async (route) => {
        const frames = deltas
            .map((delta) => `event: update\ndata: ${JSON.stringify({ delta })}\n\n`)
            .join('') + 'event: update\ndata: </stream>\n\n';

        await route.fulfill({
            status: 200,
            contentType: 'text/event-stream',
            body: frames,
        });
    });
}

async function openTutor(page: Page) {
    await page.goto(`${BASE}/`);
    await page.locator('.nb-tutor__launcher').click();

    await expect(page.locator('.nb-tutor__panel')).toBeVisible();
}

test.describe('Tutor bot', () => {
    test('opens from the launcher and closes again', async ({ page }) => {
        await page.goto(`${BASE}/`);

        await expect(page.locator('.nb-tutor__panel')).toHaveCount(0);

        await page.locator('.nb-tutor__launcher').click();
        await expect(page.locator('.nb-tutor__panel')).toBeVisible();

        await page.locator('.nb-tutor__close').click();
        await expect(page.locator('.nb-tutor__panel')).toHaveCount(0);
    });

    test('offers starter questions until the first answer', async ({ page }) => {
        await fulfilStream(page, ['Благодаря', ' (blagodarya).']);
        await openTutor(page);

        const suggestions = page.locator('.nb-tutor__suggestion');
        await expect(suggestions).toHaveCount(3);

        await suggestions.first().click();

        await expect(page.locator('.nb-tutor__turn--assistant')).toHaveText('Благодаря (blagodarya).');
        await expect(page.locator('.nb-tutor__suggestion')).toHaveCount(0);
    });

    test('streams an answer back and keeps the question in the thread', async ({ page }) => {
        await fulfilStream(page, ['Здравей', ' means hello.']);
        await openTutor(page);

        await page.locator('.nb-tutor__field').fill('How do I say hello?');
        await page.locator('.nb-tutor__send').click();

        await expect(page.locator('.nb-tutor__turn--user')).toHaveText('How do I say hello?');
        await expect(page.locator('.nb-tutor__turn--assistant')).toHaveText('Здравей means hello.');
        await expect(page.locator('.nb-tutor__field')).toHaveValue('');
    });

    test('will not send a question of one character', async ({ page }) => {
        await openTutor(page);

        await page.locator('.nb-tutor__field').fill('a');
        await expect(page.locator('.nb-tutor__send')).toBeDisabled();

        await page.locator('.nb-tutor__field').fill('ab');
        await expect(page.locator('.nb-tutor__send')).toBeEnabled();
    });

    test('says so when the tutor is rate limited', async ({ page }) => {
        await page.route('**/tutor', (route) => route.fulfill({ status: 429, body: '' }));
        await openTutor(page);

        await page.locator('.nb-tutor__field').fill('How do I say hello?');
        await page.locator('.nb-tutor__send').click();

        await expect(page.locator('.nb-tutor__error')).toContainText('a minute');
        await expect(page.locator('.nb-tutor__turn--assistant')).toHaveCount(0);
    });

    /**
     * A provider failing mid-answer arrives as its own frame, and has to
     * replace the half-written reply rather than leave it looking finished.
     */
    test('replaces a half-written answer when the stream errors', async ({ page }) => {
        await page.route('**/tutor', (route) => route.fulfill({
            status: 200,
            contentType: 'text/event-stream',
            body: 'event: update\ndata: {"delta":"Здра"}\n\n'
                + 'event: error\ndata: {"message":"The tutor is unavailable right now."}\n\n',
        }));
        await openTutor(page);

        await page.locator('.nb-tutor__field').fill('How do I say hello?');
        await page.locator('.nb-tutor__send').click();

        await expect(page.locator('.nb-tutor__error')).toContainText('unavailable');
        await expect(page.locator('.nb-tutor__turn--assistant')).toHaveCount(0);
        await expect(page.locator('.nb-tutor__turn--user')).toHaveText('How do I say hello?');
    });

    test('sends the earlier turns back with the next question', async ({ page }) => {
        await fulfilStream(page, ['Да.']);
        await openTutor(page);

        await page.locator('.nb-tutor__field').fill('How do I say hello?');
        await page.locator('.nb-tutor__send').click();
        await expect(page.locator('.nb-tutor__turn--assistant')).toHaveText('Да.');

        const second = page.waitForRequest((request) =>
            request.url().includes('/tutor') && request.method() === 'POST');

        await page.locator('.nb-tutor__field').fill('And in the plural?');
        await page.locator('.nb-tutor__send').click();

        const history = (await second).postDataJSON().history;

        expect(history).toEqual([
            { role: 'user', content: 'How do I say hello?' },
            { role: 'assistant', content: 'Да.' },
        ]);
    });
});
