import { test, expect, Page, Route } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

type Path = { id: number; name: string; language: string; type: string; exercise_types: string[]; exercise_count: number };
type Level = { value: string; label: string };
type Filters = { level: string | null; sort: string };
type Props = Record<string, unknown>;
type InertiaPage = { component: string; props: Props; url: string; version: string | null };

const PATHS: Path[] = [
    { id: 9401, name: 'A1 path', language: 'bg', type: 'regular', exercise_types: [], exercise_count: 2 },
    { id: 9402, name: 'B1 path', language: 'bg', type: 'regular', exercise_types: [], exercise_count: 4 },
];

const LEVELS: Level[] = [
    { value: 'A1', label: 'A1 Beginner' },
    { value: 'B1', label: 'B1 Intermediate' },
];

const FIRST_LOAD: Props = {
    paths: PATHS,
    unfinishedPaths: [],
    finishedPaths: [],
    search: { enabled: true, query: '', unavailable: false },
    levels: LEVELS,
    filters: { level: null, sort: 'exercises_desc' },
};

// Rewrites the page object the server rendered on first load, once the
// document is parsed and before the deferred app script mounts, so the test
// starts from exactly the catalog it describes. The HTML response itself is
// left alone: fulfilling it would make Chrome treat the page as foreign and
// refuse its scripts from the Vite dev server.
async function openCatalog(page: Page, overrides: Props = {}): Promise<InertiaPage> {
    await page.addInitScript((props: Props) => {
        document.addEventListener('readystatechange', () => {
            if (document.readyState !== 'interactive') {
                return;
            }

            const app = document.getElementById('app');

            if (!app?.dataset.page) {
                return;
            }

            const data = JSON.parse(app.dataset.page);
            data.props = { ...data.props, ...props };
            app.dataset.page = JSON.stringify(data);
        });
    }, { ...FIRST_LOAD, ...overrides });

    await page.goto(`${BASE}/learning-paths`);
    await expect(page.getByRole('heading', { name: 'All learning paths' })).toBeVisible();

    return page.evaluate(() => JSON.parse(document.getElementById('app')?.dataset.page ?? '{}'));
}

// Answers every Inertia visit to the catalog made after the first load: the
// filters echo the address, so choosing a level in the prompt is reflected
// back exactly as the server would. Full page loads pass through untouched.
async function answerVisits(page: Page, base: InertiaPage): Promise<string[]> {
    const seen: string[] = [];

    await page.route(/\/learning-paths(\?.*)?$/, async (route: Route) => {
        const request = route.request();

        if (!request.headers()['x-inertia']) {
            return route.continue();
        }

        const url = new URL(request.url());
        seen.push(url.search);

        await route.fulfill({
            status: 200,
            headers: { 'Content-Type': 'application/json', 'X-Inertia': 'true', Vary: 'X-Inertia' },
            body: JSON.stringify({
                ...base,
                url: url.pathname + url.search,
                props: {
                    ...base.props,
                    errors: {},
                    filters: { ...(base.props.filters as Filters), level: url.searchParams.get('level') },
                },
            }),
        });
    });

    return seen;
}

const prompt = (page: Page) => page.getByRole('dialog', { name: 'Choose your level' });
const a1Option = (page: Page) => prompt(page).getByRole('button', { name: 'A1 Beginner' });
const b1Option = (page: Page) => prompt(page).getByRole('button', { name: 'B1 Intermediate' });

test.describe('Learning path level prompt', () => {
    test('blocks the page until a level is chosen, with no level active yet', async ({ page }) => {
        await openCatalog(page);

        await expect(prompt(page)).toBeVisible();
        await expect(a1Option(page)).toBeVisible();
        await expect(b1Option(page)).toBeVisible();
        await expect(page.getByRole('group', { name: 'Filter learning paths by level' })
            .getByRole('button', { name: 'A1 Beginner' })).toHaveAttribute('aria-pressed', 'false');
    });

    test('choosing a level closes the prompt and narrows the page to it', async ({ page }) => {
        const base = await openCatalog(page);
        const seen = await answerVisits(page, base);

        await b1Option(page).click();

        await expect(page).toHaveURL(`${BASE}/learning-paths?level=B1&sort=exercises_desc`);
        expect(seen).toEqual(['?level=B1&sort=exercises_desc']);
        await expect(prompt(page)).toBeHidden();
    });

    test('escape does not dismiss the prompt', async ({ page }) => {
        await openCatalog(page);
        await expect(prompt(page)).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(prompt(page)).toBeVisible();
    });

    test('is absent once a level is already active', async ({ page }) => {
        await openCatalog(page, { filters: { level: 'A1', sort: 'exercises_desc' } });

        await expect(prompt(page)).toBeHidden();
    });

    test('is absent when no path has any level', async ({ page }) => {
        await openCatalog(page, { levels: [] });

        await expect(prompt(page)).toBeHidden();
    });

    test('follows the dark theme', async ({ page }) => {
        await page.addInitScript(() => localStorage.setItem('theme', 'dark'));
        await openCatalog(page);

        await expect(prompt(page)).toHaveClass(/\bdark\b/);
    });
});
