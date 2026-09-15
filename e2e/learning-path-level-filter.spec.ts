import { test, expect, Page, Route } from '@playwright/test';

// Host base URL — override with APP_URL when Sail maps to a non-default port.
const BASE = process.env.APP_URL ?? 'http://localhost';

type Path = { id: number; name: string; language: string; type: string; exercise_types: string[]; exercise_count: number };
type Level = { value: string; label: string };
type Filters = { level: string; sort: string };
type Props = Record<string, unknown>;
type InertiaPage = { component: string; props: Props; url: string; version: string | null };

const PATHS: Path[] = [
    { id: 9301, name: 'A2 path', language: 'bg', type: 'regular', exercise_types: [], exercise_count: 3 },
];

const LEVELS: Level[] = [
    { value: 'A1', label: 'A1 Beginner' },
    { value: 'A2', label: 'A2 Elementary' },
    { value: 'B1', label: 'B1 Intermediate' },
];

const FIRST_LOAD: Props = {
    paths: PATHS,
    unfinishedPaths: [],
    finishedPaths: [],
    search: { enabled: true, query: '', unavailable: false },
    levels: LEVELS,
    filters: { level: 'A2', sort: 'exercises_desc' },
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
// filters echo the address, and the catalog is narrowed to paths at the
// requested level. Full page loads pass through untouched.
async function answerVisits(page: Page, base: InertiaPage, delayMs = 0): Promise<string[]> {
    const seen: string[] = [];

    await page.route(/\/learning-paths(\?.*)?$/, async (route: Route) => {
        const request = route.request();

        if (!request.headers()['x-inertia']) {
            return route.continue();
        }

        const url = new URL(request.url());
        seen.push(url.search);

        const level = url.searchParams.get('level') ?? 'A2';

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
                    paths: level === 'A2' ? PATHS : [],
                    filters: { ...(base.props.filters as Filters), level },
                },
            }),
        });
    });

    return seen;
}

const filter = (page: Page) => page.getByRole('group', { name: 'Filter learning paths by level' });
const a1Button = (page: Page) => filter(page).getByRole('button', { name: 'A1 Beginner' });
const a2Button = (page: Page) => filter(page).getByRole('button', { name: 'A2 Elementary' });
const b1Button = (page: Page) => filter(page).getByRole('button', { name: 'B1 Intermediate' });

test.describe('Learning path level filter', () => {
    test('defaults to A2 with no way to clear it back to every level', async ({ page }) => {
        await openCatalog(page);

        await expect(filter(page)).toBeVisible();
        await expect(a2Button(page)).toHaveAttribute('aria-pressed', 'true');
        await expect(a1Button(page)).toHaveAttribute('aria-pressed', 'false');
        await expect(filter(page).getByRole('button', { name: 'All' })).toHaveCount(0);
    });

    test('switches to another level', async ({ page }) => {
        const base = await openCatalog(page);
        const seen = await answerVisits(page, base);

        await b1Button(page).click();

        await expect(page).toHaveURL(`${BASE}/learning-paths?level=B1&sort=exercises_desc`);
        expect(seen).toEqual(['?level=B1&sort=exercises_desc']);
        await expect(b1Button(page)).toHaveAttribute('aria-pressed', 'true');
        await expect(a2Button(page)).toHaveAttribute('aria-pressed', 'false');
    });

    test('does nothing when the already-active level is clicked again', async ({ page }) => {
        const base = await openCatalog(page);
        const seen = await answerVisits(page, base);

        await a2Button(page).click();

        expect(seen).toEqual([]);
        await expect(page).toHaveURL(`${BASE}/learning-paths`);
    });

    test('disables every button while the narrowed list loads', async ({ page }) => {
        const base = await openCatalog(page);
        await answerVisits(page, base, 800);

        await b1Button(page).click();

        await expect(b1Button(page)).toBeDisabled();
        await expect(a1Button(page)).toBeDisabled();
        await expect(a2Button(page)).toBeDisabled();

        await expect(b1Button(page)).toBeEnabled();
        await expect(a1Button(page)).toBeEnabled();
        await expect(a2Button(page)).toBeEnabled();
    });

    test('keeps the search and the sort already narrowing the page', async ({ page }) => {
        const base = await openCatalog(page, {
            search: { enabled: true, query: 'town', unavailable: false },
            filters: { level: 'A2', sort: 'exercises_asc' },
        });
        const seen = await answerVisits(page, base);

        await b1Button(page).click();

        await expect.poll(() => seen).toEqual(['?q=town&level=B1&sort=exercises_asc']);
        await expect(page.getByRole('searchbox', { name: 'Search learning paths by topic' })).toHaveValue('town');
    });

    test('is absent when no path has any level', async ({ page }) => {
        await openCatalog(page, { levels: [] });

        await expect(page.getByRole('group', { name: 'Filter learning paths by level' })).toHaveCount(0);
    });

    test('going back restores the earlier level', async ({ page }) => {
        const base = await openCatalog(page);
        await answerVisits(page, base);

        await b1Button(page).click();
        await expect(page).toHaveURL(`${BASE}/learning-paths?level=B1&sort=exercises_desc`);

        await page.goBack();

        await expect(page).toHaveURL(`${BASE}/learning-paths`);
        await expect(a2Button(page)).toHaveAttribute('aria-pressed', 'true');
    });

    test('follows the dark theme', async ({ page }) => {
        await page.addInitScript(() => localStorage.setItem('theme', 'dark'));
        await openCatalog(page);

        await expect(filter(page)).toHaveClass(/\bdark\b/);
    });
});
