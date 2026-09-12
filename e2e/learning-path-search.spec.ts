import { test, expect, Page, Route } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

type Path = { id: number; name: string; language: string; type: string; exercise_types: string[] };
type Search = { enabled: boolean; query: string; unavailable: boolean };
type InertiaPage = { component: string; props: Record<string, unknown>; url: string; version: string | null };

const PATHS: Path[] = [
    { id: 9001, name: 'Food and drink', language: 'bg', type: 'regular', exercise_types: [] },
    { id: 9002, name: 'At the market', language: 'bg', type: 'regular', exercise_types: [] },
];

// The page object the server rendered on first load, read back from the root
// element so the faked responses below carry the real component name, asset
// version and shared props, and differ from a genuine answer only in the
// search results they describe.
async function initialPage(page: Page): Promise<InertiaPage> {
    return page.evaluate(() => {
        const script = document.querySelector('script[data-page="app"]');
        const raw = script ? script.textContent : document.getElementById('app')?.dataset.page;

        return JSON.parse(raw ?? '{}');
    });
}

// Answers every Inertia visit to the catalog made after the first load, so a
// search never reaches the embedding provider and each test decides exactly
// what came back. The reply is built per request from the text it carried;
// `delayMs` holds it back to leave the in-flight state observable. Full page
// loads are passed through untouched.
async function answerSearches(
    page: Page,
    base: InertiaPage,
    reply: (query: string) => { paths: Path[]; search?: Partial<Search> },
    delayMs = 0,
): Promise<string[]> {
    const seen: string[] = [];

    await page.route(/\/learning-paths(\?.*)?$/, async (route: Route) => {
        const request = route.request();

        if (!request.headers()['x-inertia']) {
            return route.continue();
        }

        const url = new URL(request.url());
        const query = url.searchParams.get('q') ?? '';
        seen.push(url.search);

        const { paths, search } = reply(query);

        if (delayMs) {
            await new Promise((resolve) => setTimeout(resolve, delayMs));
        }

        await route.fulfill({
            status: 200,
            headers: { 'Content-Type': 'application/json', 'X-Inertia': 'true', Vary: 'X-Inertia' },
            body: JSON.stringify({
                ...base,
                url: url.pathname + url.search,
                props: {
                    ...base.props,
                    errors: {},
                    paths,
                    unfinishedPaths: [],
                    finishedPaths: [],
                    search: { enabled: true, query, unavailable: false, ...search },
                },
            }),
        });
    });

    return seen;
}

async function openCatalog(page: Page): Promise<InertiaPage> {
    await page.goto(`${BASE}/learning-paths`);
    await expect(page.getByRole('heading', { name: 'All learning paths' })).toBeVisible();

    return initialPage(page);
}

const field = (page: Page) => page.getByRole('searchbox', { name: 'Search learning paths by topic' });
const submitButton = (page: Page) => page.locator('.nb-path-search__submit');
const clearButton = (page: Page) => page.getByRole('button', { name: 'Clear' });
const status = (page: Page) => page.locator('.nb-path-search__status');

async function search(page: Page, text: string): Promise<void> {
    await field(page).fill(text);
    await submitButton(page).click();
}

test.describe('Learning path search', () => {
    test('starts empty, with no status and no way to clear', async ({ page }) => {
        await openCatalog(page);

        await expect(field(page)).toBeVisible();
        await expect(field(page)).toHaveValue('');
        await expect(field(page)).toHaveAttribute('placeholder', 'Search by topic, e.g. food or greetings');
        await expect(submitButton(page)).toHaveText('Search');
        await expect(clearButton(page)).toHaveCount(0);
        await expect(status(page)).toHaveCount(0);
        await expect(page.getByRole('search')).toBeVisible();
    });

    test('sends the text as q and lists what matched with a count', async ({ page }) => {
        const base = await openCatalog(page);
        const seen = await answerSearches(page, base, () => ({ paths: PATHS }));

        await search(page, 'food');

        await expect(status(page)).toHaveText('2 paths match “food”.');
        await expect(page).toHaveURL(`${BASE}/learning-paths?q=food`);
        expect(seen).toEqual(['?q=food']);
        await expect(page.locator('.nb-paths__grid-item')).toHaveCount(2);
        await expect(page.getByText('Food and drink')).toBeVisible();
        await expect(clearButton(page)).toBeVisible();
    });

    test('says "path matches" for a single result', async ({ page }) => {
        const base = await openCatalog(page);
        await answerSearches(page, base, () => ({ paths: PATHS.slice(0, 1) }));

        await search(page, 'market');

        await expect(status(page)).toHaveText('1 path matches “market”.');
    });

    test('says nothing matched and drops the catalog section instead of an empty-state card', async ({ page }) => {
        const base = await openCatalog(page);
        await answerSearches(page, base, () => ({ paths: [] }));

        await search(page, 'astronomy');

        await expect(status(page)).toHaveText('No learning paths match “astronomy”.');
        await expect(page.locator('.nb-paths__empty')).toHaveCount(0);
        await expect(page.locator('.nb-paths__grid')).toHaveCount(0);
    });

    test('trims the text before searching', async ({ page }) => {
        const base = await openCatalog(page);
        const seen = await answerSearches(page, base, () => ({ paths: PATHS }));

        await search(page, '   food  ');

        await expect(status(page)).toHaveText('2 paths match “food”.');
        expect(seen).toEqual(['?q=food']);
    });

    // A blank field asks for the whole catalog, so the visit goes out with no
    // q at all rather than an empty one the server would treat as a search.
    test('submitting a blank field loads the whole catalog', async ({ page }) => {
        const base = await openCatalog(page);
        const seen = await answerSearches(page, base, () => ({ paths: PATHS }));

        await search(page, '   ');

        await expect.poll(() => seen).toEqual(['']);
        await expect(page).toHaveURL(`${BASE}/learning-paths`);
        await expect(status(page)).toHaveCount(0);
    });

    test('disables both buttons and says so while a search is in flight', async ({ page }) => {
        const base = await openCatalog(page);
        await answerSearches(page, base, () => ({ paths: PATHS }), 800);

        await search(page, 'food');

        await expect(submitButton(page)).toBeDisabled();
        await expect(submitButton(page)).toHaveText('Searching');
        await expect(submitButton(page)).toHaveAttribute('aria-busy', 'true');

        await expect(status(page)).toHaveText('2 paths match “food”.');
        await expect(submitButton(page)).toBeEnabled();
        await expect(submitButton(page)).toHaveText('Search');
        await expect(submitButton(page)).toHaveAttribute('aria-busy', 'false');
    });

    test('clears back to the whole catalog', async ({ page }) => {
        const base = await openCatalog(page);
        const seen = await answerSearches(page, base, (query) => ({ paths: query ? PATHS.slice(0, 1) : PATHS }));

        await search(page, 'food');
        await expect(status(page)).toHaveText('1 path matches “food”.');

        await clearButton(page).click();

        await expect(page).toHaveURL(`${BASE}/learning-paths`);
        await expect(field(page)).toHaveValue('');
        await expect(status(page)).toHaveCount(0);
        await expect(clearButton(page)).toHaveCount(0);
        expect(seen).toEqual(['?q=food', '']);
    });

    // The field follows the query the page was answered for, so stepping back
    // through history shows the text that produced the list on screen.
    test('going back restores the earlier search text and results', async ({ page }) => {
        const base = await openCatalog(page);
        await answerSearches(page, base, (query) => ({ paths: query === 'food' ? PATHS : PATHS.slice(0, 1) }));

        await search(page, 'food');
        await expect(status(page)).toHaveText('2 paths match “food”.');
        await search(page, 'market');
        await expect(status(page)).toHaveText('1 path matches “market”.');

        await page.goBack();

        await expect(page).toHaveURL(`${BASE}/learning-paths?q=food`);
        await expect(field(page)).toHaveValue('food');
        await expect(status(page)).toHaveText('2 paths match “food”.');
    });

    test('says search is unavailable when the server could not run it', async ({ page }) => {
        const base = await openCatalog(page);
        await answerSearches(page, base, () => ({ paths: PATHS, search: { unavailable: true } }));

        await search(page, 'food');

        await expect(page.getByRole('alert')).toHaveText('Search is unavailable right now. Showing every learning path instead.');
        await expect(status(page)).toHaveCount(0);
        await expect(page.locator('.nb-paths__grid-item')).toHaveCount(2);
    });

    // Not intercepted: the real server rejects a one-character query in
    // validation, before any embedding call is made.
    test('shows the validation message for a one-character query', async ({ page }) => {
        await openCatalog(page);

        await search(page, 'a');

        const error = page.locator('#nb-path-search-error');
        await expect(error).toHaveText('Type at least 2 characters to search.');
        await expect(error).toHaveAttribute('role', 'alert');
        await expect(field(page)).toHaveAttribute('aria-invalid', 'true');
        await expect(field(page)).toHaveAttribute('aria-describedby', 'nb-path-search-error');
        await expect(field(page)).toHaveClass(/nb-path-search__input--invalid/);
        await expect(status(page)).toHaveCount(0);
    });

    // Turning search off for real would hide the field from every spec running
    // in parallel, so the server's first-load page object is edited instead to
    // say what the controller sends while search is off. The edit happens in
    // the page, once the document is parsed and before the deferred app script
    // mounts: replacing the HTML response itself would make Chrome treat the
    // page as foreign and refuse its scripts from the Vite dev server.
    test('is left out entirely when search is turned off', async ({ page }) => {
        await page.addInitScript(() => {
            document.addEventListener('readystatechange', () => {
                const app = document.getElementById('app');

                if (document.readyState !== 'interactive' || !app?.dataset.page) {
                    return;
                }

                const data = JSON.parse(app.dataset.page);
                data.props.search = { enabled: false, query: '', unavailable: false };
                app.dataset.page = JSON.stringify(data);
            });
        });

        await page.goto(`${BASE}/learning-paths`);

        await expect(page.getByRole('heading', { name: 'All learning paths' })).toBeVisible();
        await expect(page.locator('.nb-paths__grid, .nb-paths__empty').first()).toBeVisible();
        await expect(page.locator('.nb-path-search')).toHaveCount(0);
        await expect(page.getByRole('search')).toHaveCount(0);
    });

    test('follows the dark theme', async ({ page }) => {
        await page.addInitScript(() => localStorage.setItem('theme', 'dark'));
        await openCatalog(page);

        await expect(page.locator('.nb-path-search')).toHaveClass(/\bdark\b/);
    });
});
